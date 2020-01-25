<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Setup;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Utils\CliMsgFormatter;

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class RebuildElasticMissingDocuments extends \Maintenance {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DocumentReplicationExaminer
	 */
	private $documentReplicationExaminer;

	/**
	 * @var JobFactory
	 */
	private $jobFactory;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var int
	 */
	private $lastId = 0;

	/**
	 * @since 3.1
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription(
			"Find missing entities (aka. documents) in Elasticsearch and schedule appropriate update jobs."
		);
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

		$applicationFactory = ApplicationFactory::getInstance();
		$this->store = $applicationFactory->getStore();
		$this->jobFactory = $applicationFactory->newJobFactory();

		$elasticFactory = $applicationFactory->create( 'ElasticFactory' );
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$this->documentReplicationExaminer = $elasticFactory->newDocumentReplicationExaminer(
			$this->store
		);

		if ( !$this->store->getConnection( 'elastic' )->ping() ) {

			$this->reportMessage(
				"\n" . 'Elasticsearch endpoint(s) are not available!' . "\n"
			);

			return true;
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"The script checks for missing entities (aka. documents) in",
			"Elasticsearch. If documents have been classified as missing",
			"(i.e not found, showed divergent meta data such as modification date",
			"or associated revision) then update jobs will be executed."
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Document search and update' )
		);

		$this->reportMessage( "\nSelecting entities ...\n" );

		$this->reportMessage(
			$cliMsgFormatter->firstCol( '   ... fetching from table ...' )
		);

		$rows = $this->fetchRows();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$report = $this->checkAndRebuild( $rows );

		$this->reportMessage(
			"\n" . $cliMsgFormatter->firstCol( "... removed replication trail", 3 )
		);

		$checkReplicationTask = $elasticFactory->newCheckReplicationTask(
			$this->store
		);

		$checkReplicationTask->deleteEntireReplicationTrail();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->reportMessage( "   ... done.\n" );

		$this->reportMessage(
			$cliMsgFormatter->section( "Summary" ) . "\n"
		);

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "- Document(s) missing", $report['notExists_count'], 0, '.' )
		);

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "- Document(s) with divergent revision/date", $report['dataDiff_count'], 0, '.' )
		);

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "- Update job(s) run", $report['jobs'], 0, '.' )
		);

		return true;
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

	private function fetchRows() {

		$connection = $this->store->getConnection( 'mw.db' );

		$this->lastId = (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			'',
			__METHOD__
		);

		return $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			],
			[
				"smw_subobject=''",
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWREDIIW )
			],
			__METHOD__,
			[ 'ORDER BY' => 'smw_id' ]
		);
	}

	private function checkAndRebuild( \Iterator $rows ) {

		$cliMsgFormatter = new CliMsgFormatter();
		$connection = $this->store->getConnection( 'mw.db' );

		$count = $rows->numRows();
		$i = 0;
		$notExists_count = 0;
		$dataDiff_count = 0;

		if ( $count == 0 ) {
			return $this->reportMessage( "   ... no entities selected ...\n"  );
		}

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... found ...", "(rows) $count", 3 )
		);

		$this->reportMessage(
			$cliMsgFormatter->oneCol( "... done.", 3 )
		);

		$this->reportMessage( "\nInspecting documents ...\n" );
		$cliMsgFormatter->setStartTime( microtime( true ) );

		foreach ( $rows as $row ) {

			if ( $row->smw_title === '_INST' ) {
				continue;
			}

			$progress = $cliMsgFormatter->progressCompact( ++$i, $count, $row->smw_id, $this->lastId );

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride( "... checking ID to document reference ...", $progress, 3 )
			);

			$subject = $this->newFromRow( $row );
			$title = $subject->getTitle();

			if ( $title === null ) {
				continue;
			}

			if ( !$this->documentReplicationExaminer->documentExistsById( $row->smw_id ) ) {
				$notExists_count++;
				$this->runJob( $title );
			} elseif ( $title->exists() && ( $res = $this->documentReplicationExaminer->check( $subject ) ) !== [] ) {
				$dataDiff_count++;
				$this->runJob( $title );
			}
		}

		return [
			'notExists_count' => $notExists_count,
			'dataDiff_count' => $dataDiff_count,
			'jobs' => $notExists_count + $dataDiff_count
		];
	}

	private function runJob( $title ) {

		$job = $this->jobFactory->newUpdateJob(
			$title
		);

		$job->run();
	}

	public function newFromRow( $row ) {

		$namespace = (int)$row->smw_namespace;
		$title = $row->smw_title;

		if ( $namespace === SMW_NS_PROPERTY ) {
			try {
				$property = DIProperty::newFromUserLabel( $row->smw_title );
				$title = str_replace( ' ', '_', $property->getLabel() );
			} catch( PropertyLabelNotResolvedException $e ) {
				//
			} catch( PredefinedPropertyLabelMismatchException $e ) {
				//
			}
		}

		$subject = new DIWikiPage(
			$title,
			$namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		return $subject;
	}

}

$maintClass = 'SMW\Maintenance\RebuildElasticMissingDocuments';
require_once( RUN_MAINTENANCE_IF_MAIN );
