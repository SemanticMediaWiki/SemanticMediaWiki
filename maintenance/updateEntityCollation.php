<?php

namespace SMW\Maintenance;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;
use Onoi\MessageReporter\MessageReporter;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SetupFile;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableFieldUpdater;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\PeriodicStatsFlusher;
use stdClass;
use Throwable;
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
 * @since 3.0
 *
 * @author mwjames
 */
class updateEntityCollation extends Maintenance {

	/**
	 * Incomplete task message
	 */
	const ENTITY_COLLATION_INCOMPLETE = 'smw-updateentitycollation-incomplete';

	/**
	 * @var SQLStore
	 */
	private $store;

	private ?HookContainer $hookContainer = null;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Update the smw_sort field (relying on the $smwgEntityCollation setting)';
		$this->addOption( 's', 'ID starting point', false, true );
		$this->setBatchSize( 1000 );
	}

	/**
	 * @since 7.0.0
	 */
	public function setHookContainer( HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @since 3.2
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {
		global $wgCategoryCollation;

		$maintenanceCheck = new MaintenanceCheck();
		if ( !$maintenanceCheck->canExecute() ) {
			exit( $maintenanceCheck->getMessage() );
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();
		$setupFile = $applicationFactory->singleton( 'SetupFile' );

		// @phan-suppress-next-line PhanTypeMismatchProperty
		$this->store = $applicationFactory->getStore(
			SQLStore::class
		);

		if ( $this->hookContainer === null ) {
			$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		}

		if ( $this->messageReporter === null ) {
			$this->messageReporter = $maintenanceFactory->newMessageReporter( [ $this, 'reportMessage' ] );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$intl = 'N/A';

		if ( extension_loaded( 'intl' ) ) {
			$intl = phpversion( 'intl' ) . ' / ' . INTL_ICU_VERSION;
		}

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->twoCols( 'Intl / ICU:', $intl )
		);

		$smwgEntityCollation = $applicationFactory->getSettings()->get( 'smwgEntityCollation' );

		if ( $smwgEntityCollation !== $wgCategoryCollation ) {
			$this->informAboutDifferences( $smwgEntityCollation, $wgCategoryCollation );
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Collation update(s)' )
		);

		$this->messageReporter->reportMessage(
			"\nRunning `$smwgEntityCollation` update ...\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( '... fetching from table ...', 3 )
		);

		$startId = (int)$this->getOption( 's', 0 );
		$count = $this->countRows( $startId );

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( "... found `smw_sort` field records ...", "$count (rows)", 3 )
		);

		$this->runUpdate( $startId, $count );
		$this->messageReporter->reportMessage( "\n   ... done.\n" );

		$setupFile->removeIncompleteTask( self::ENTITY_COLLATION_INCOMPLETE );

		$setupFile->set(
			[
				SetupFile::ENTITY_COLLATION => $smwgEntityCollation
			]
		);

		$this->hookContainer->run(
			'SMW::Maintenance::AfterUpdateEntityCollationComplete',
			[ $this->store, $this->messageReporter ]
		);
	}

	private function informAboutDifferences( $smwgEntityCollation, $categoryCollation ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Notice' )
		);

		$text = [
			"The `smwgEntityCollation` and `wgCategoryCollation` have different",
			"collation settings and may therefore result in an inconsistent sorting",
			"display for entities."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->oneCol( 'Collation settings ...' )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( '... `$smwgEntityCollation`', $smwgEntityCollation, 3, '.' )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( '... `$wgCategoryCollation`', $categoryCollation, 3, '.' )
		);
	}

	private function countRows( int $startId ): int {
		return (int)$this->store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( SQLStore::ID_TABLE )
			->where( $this->newConditions( $startId ) )
			->caller( __METHOD__ )
			->fetchField();
	}

	private function runUpdate( int $startId, int $count ): void {
		$connection = $this->store->getConnection( 'mw.db' );
		$tableFieldUpdater = new TableFieldUpdater( $this->store );
		$cliMsgFormatter = new CliMsgFormatter();
		$statsFlusher = PeriodicStatsFlusher::newFromGlobalState();
		$lastId = $this->fetchLastId();
		$afterId = $startId;
		$updated = 0;
		$rows = $this->fetchBatch( $afterId );

		while ( $rows->numRows() > 0 ) {
			// Commit per batch: each commit waits for a disk sync
			$connection->beginSectionTransaction( __METHOD__ );

			try {
				foreach ( $rows as $row ) {
					$statsFlusher->tick();
					$afterId = (int)$row->smw_id;
					$tableFieldUpdater->updateSortField( $afterId, $this->getSortKey( $row ) );
				}

				$connection->endSectionTransaction( __METHOD__ );
			} catch ( Throwable $e ) {
				$connection->cancelSectionTransaction( __METHOD__ );
				throw $e;
			}

			// Report only committed IDs, so that the shown ID is a safe `-s` to resume from
			$updated += $rows->numRows();
			$progress = $cliMsgFormatter->progressCompact( $updated, $count, $afterId, $lastId );

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoColsOverride( "... updating entity (current/last) ...", $progress, 3 )
			);

			$this->waitForReplication();
			$rows = $this->fetchBatch( $afterId );
		}
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
				'smw_sortkey'
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
			$connection->expr( 'smw_iw', '!=', [ SMW_SQL3_SMWIW_OUTDATED, SMW_SQL3_SMWDELETEIW ] ),
			$connection->expr( 'smw_title', '!=', '' ),
			$connection->expr( 'smw_id', '>', $afterId )
		];
	}

	private function getSortKey( stdClass $row ): string {
		if ( $row->smw_sortkey !== '' ) {
			return $row->smw_sortkey;
		}

		return str_replace( '_', ' ', $row->smw_title );
	}

}

// @codeCoverageIgnoreStart
$maintClass = updateEntityCollation::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
