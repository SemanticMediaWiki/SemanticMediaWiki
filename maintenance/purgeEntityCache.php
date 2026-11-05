<?php

namespace SMW\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use Onoi\MessageReporter\MessageReporter;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Setup;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\PeriodicStatsFlusher;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Load the required class
 */
// @codeCoverageIgnoreStart
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}
// @codeCoverageIgnoreEnd

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class purgeEntityCache extends Maintenance {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.1
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Purge cache entries for known entities and their associated data." );
		$this->setBatchSize( 1000 );
	}

	/**
	 * @since 7.0.0
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 7.0.0
	 */
	public function setEntityCache( EntityCache $entityCache ) {
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		if ( $this->messageReporter !== null ) {
			return $this->messageReporter->reportMessage( $message );
		}

		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {
		if ( $this->canExecute() !== true ) {
			exit;
		}

		$cliMsgFormatter = new CliMsgFormatter();
		$applicationFactory = ApplicationFactory::getInstance();

		$this->store ??= $applicationFactory->getStore();
		$this->entityCache ??= $applicationFactory->getEntityCache();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"The script purges cache entries and their associate data for actively",
			"used entities."
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Cache purge' )
		);

		$this->reportMessage( "\nSelecting entities ...\n" );

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... counting rows in table ...", 3 )
		);

		$count = $this->countRows();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->doPurge( $count );

		$this->reportMessage( "   ... done.\n" );

		return true;
	}

	private function countRows(): int {
		return (int)$this->store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( SQLStore::ID_TABLE )
			->where( $this->newConditions( 0 ) )
			->caller( __METHOD__ )
			->fetchField();
	}

	private function fetchLastId(): int {
		return (int)$this->store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'MAX(smw_id)' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchField();
	}

	private function fetchBatch( int $afterId ): IResultWrapper {
		return $this->store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( [
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			] )
			->from( SQLStore::ID_TABLE )
			->where( $this->newConditions( $afterId ) )
			->orderBy( 'smw_id' )
			->limit( $this->getBatchSize() )
			->caller( __METHOD__ )
			->fetchResultSet();
	}

	/**
	 * @return IExpression[]
	 */
	private function newConditions( int $afterId ): array {
		$connection = $this->store->getConnection( 'mw.db' );

		return [
			$connection->expr( 'smw_subobject', '=', '' ),
			$connection->expr( 'smw_iw', '!=', SMW_SQL3_SMWDELETEIW ),
			$connection->expr( 'smw_id', '>', $afterId )
		];
	}

	private function canExecute() {
		if ( !Setup::isEnabled() ) {
			return $this->reportMessage(
				"\nYou need to have SMW enabled in order to run the maintenance script!\n"
			);
		}

		if ( !Setup::isValid( true ) ) {
			return $this->reportMessage(
				"\nYou need to run `update.php` or `setupStore.php` first before continuing\n" .
				"with this maintenance task!\n"
			);
		}

		return true;
	}

	private function doPurge( int $count ): void {
		$cliMsgFormatter = new CliMsgFormatter();

		if ( $count === 0 ) {
			$this->reportMessage( "   ... no entities selected ...\n" );
			return;
		}

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... entities matched ...", "(rows) $count", 3 )
		);

		$statsFlusher = PeriodicStatsFlusher::newFromGlobalState();
		$lastId = $this->fetchLastId();
		$afterId = 0;
		$purged = 0;
		$rows = $this->fetchBatch( $afterId );

		while ( $rows->numRows() > 0 ) {
			$subjects = [];

			foreach ( $rows as $row ) {
				$statsFlusher->tick();
				$afterId = (int)$row->smw_id;
				$subjects[] = $this->newFromRow( $row );
			}

			// One cache write per batch: with the object cache in the database, each write is a commit
			$this->entityCache->invalidateMulti( $subjects );
			$purged += $rows->numRows();

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride(
					"... invalidating cache for ...",
					$cliMsgFormatter->progressCompact( $purged, $count, $afterId, $lastId ),
					3
				)
			);

			$this->waitForReplication();
			$rows = $this->fetchBatch( $afterId );
		}

		$this->reportMessage( "\n" );
	}

	public function newFromRow( $row ) {
		$namespace = (int)$row->smw_namespace;
		$title = $row->smw_title;

		if ( $namespace === SMW_NS_PROPERTY ) {
			try {
				$property = Property::newFromUserLabel( $row->smw_title );
				$title = str_replace( ' ', '_', $property->getLabel() );
			} catch ( PredefinedPropertyLabelMismatchException ) {
				//
			}
		}

		$subject = new WikiPage(
			$title,
			$namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		return $subject;
	}

}

// @codeCoverageIgnoreStart
$maintClass = purgeEntityCache::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
