<?php

namespace SMW\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use Onoi\MessageReporter\MessageReporter;
use SMW\DataItems\WikiPage;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\HmacSerializer;
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
 * @since 3.2
 *
 * @author mwjames
 */
class updateEntityCountMap extends Maintenance {

	/**
	 * Incomplete task message
	 */
	const COUNTMAP_INCOMPLETE = 'smw-updateentitycountmap-incomplete';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

	/**
	 * @since 3.2
	 */
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Updates the `smw_countmap` field';
		$this->addOption( 's', 'Update only the entities after this ID, such as the last ID a stopped run reported', false, true );
		$this->setBatchSize( 1000 );
	}

	/**
	 * @since 3.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.2
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
		$maintenanceCheck = new MaintenanceCheck();
		if ( !$maintenanceCheck->canExecute() ) {
			exit( $maintenanceCheck->getMessage() );
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$this->store = $applicationFactory->getStore(
			SQLStore::class
		);

		if ( $this->messageReporter === null ) {
			$this->messageReporter = $maintenanceFactory->newMessageReporter(
				[ $this, 'reportMessage' ]
			);
		}

		$this->cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->head()
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'About' )
		);

		$text = [
			"This script runs an update on the `smw_countmap` field."
		];

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Field update' ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->oneCol( "Fetching fields ..." )
		);

		$startId = (int)$this->getOption( 's', 0 );
		$count = $this->countRows( $startId );
		$setupFile = $applicationFactory->singleton( 'SetupFile' );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( "... estimating required updates ...", "{$count} (rows)", 3 )
		);

		$this->runUpdate( $startId, $count );

		$setupFile->removeIncompleteTask( self::COUNTMAP_INCOMPLETE );
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
		$statsFlusher = PeriodicStatsFlusher::newFromGlobalState();
		$lastId = $this->fetchLastId();
		$afterId = $startId;
		$updated = 0;
		$rows = $this->fetchBatch( $afterId );

		while ( $rows->numRows() > 0 ) {
			// getSemanticData() reads each entity's sequence map, so load the batch's in one query
			$this->store->getObjectIds()->loadSequenceMap( $this->fetchIds( $rows ) );
			$countMaps = [];

			foreach ( $rows as $row ) {
				$statsFlusher->tick();
				$afterId = (int)$row->smw_id;
				$countMaps[$afterId] = $this->newCountMap( $row );
			}

			// Commit per batch, since each commit waits for a disk sync, and only around
			// the writes, so that the batch holds its row locks just while writing
			$connection->beginSectionTransaction( __METHOD__ );

			try {
				foreach ( $countMaps as $id => $countMap ) {
					$this->updateCountMap( $id, $countMap );
				}
			} catch ( Throwable $e ) {
				$connection->cancelSectionTransaction( __METHOD__ );
				throw $e;
			}

			$connection->endSectionTransaction( __METHOD__ );

			// Report only committed IDs, so that the shown ID is a safe `-s` to resume from
			$updated += $rows->numRows();
			$progress = $this->cliMsgFormatter->progressCompact( $updated, $count, $afterId, $lastId );

			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->twoColsOverride( "... updating (current/last) ...", $progress, 3 )
			);

			$this->waitForReplication();
			$rows = $this->fetchBatch( $afterId );
		}

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->oneCol( "... done.", 3 )
		);
	}

	/**
	 * @return int[]
	 */
	private function fetchIds( IResultWrapper $rows ): array {
		$ids = [];

		foreach ( $rows as $row ) {
			$ids[] = (int)$row->smw_id;
		}

		return $ids;
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
	 * @return array<IExpression|string>
	 */
	private function newConditions( int $afterId ): array {
		$connection = $this->store->getConnection( 'mw.db' );

		return [
			'smw_proptable_hash IS NOT NULL',
			$connection->expr( 'smw_iw', '!=', [ SMW_SQL3_SMWDELETEIW, SMW_SQL3_SMWINTDEFIW ] ),
			$connection->expr( 'smw_id', '>', $afterId )
		];
	}

	private function newCountMap( stdClass $row ): array {
		$subject = new WikiPage(
			$row->smw_title,
			(int)$row->smw_namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		$semanticData = $this->store->getSemanticData( $subject );
		$countMap = [];

		foreach ( $semanticData->getProperties() as $property ) {

			$key = $property->getKey();
			$pv = $semanticData->getPropertyValues(
				$property
			);

			if ( $key === '_INST' ) {
				$countMap[$key] ??= [];

				foreach ( $pv as $dataItem ) {
					$countMap[$key] += [ $dataItem->getDBKey() => 1 ];
				}

			} else {
				$countMap[$key] = count( $pv );
			}
		}

		return $countMap;
	}

	private function updateCountMap( int $id, array $countMap ): void {
		$connection = $this->store->getConnection( 'mw.db' );

		$blob = $connection->escape_bytea(
			HmacSerializer::compress( $countMap )
		);

		$connection->newInsertQueryBuilder()
			->insertInto( SQLStore::ID_AUXILIARY_TABLE )
			->row( [
				'smw_id' => $id,
				'smw_countmap' => $blob
			] )
			->onDuplicateKeyUpdate()
			->uniqueIndexFields( [ 'smw_id' ] )
			->set( [
				'smw_countmap' => $blob
			] )
			->caller( __METHOD__ )
			->execute();
	}

}

// @codeCoverageIgnoreStart
$maintClass = updateEntityCountMap::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
