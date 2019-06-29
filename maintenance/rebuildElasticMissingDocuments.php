<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Setup;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv('MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

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
		$this->mDescription = "Find missing entities in Elasticsearch and schedule appropriate update jobs.";
		parent::__construct();
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

		if ( !Setup::isEnabled() ) {
			$this->reportMessage( "\nYou need to have Semantic MediaWiki enabled in order to run the maintenance script!\n" );
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

		$this->reportMessage(
			"\nThe script checks for missing entities (aka. documents) in\n" .
			"Elasticsearch. It will schedule update jobs for those documents\n" .
			"that have been classified as missing or showed a divergent meta\n" .
			"data (e.g. modification date or associated revision) record.\n"
		);

		$this->reportMessage( "\nSelecting entities ...\n" );
		$this->checkAndRebuild( $this->fetchRows() );

		$this->reportMessage( "   ... removing replication trail ...\n"  );

		$checkReplicationTask = $elasticFactory->newCheckReplicationTask(
			$this->store
		);

		$checkReplicationTask->deleteEntireReplicationTrail();

		$this->reportMessage( "   ... done.\n" );

		return true;
	}

	/**
	 * @see Maintenance::addDefaultParams
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();
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

		$connection = $this->store->getConnection( 'mw.db' );

		$count = $rows->numRows();
		$i = 0;
		$notExists_count = 0;
		$dataDiff_count = 0;

		if ( $count == 0 ) {
			return $this->reportMessage( "   ... no entities selected ...\n"  );
		}

		$this->reportMessage( "   ... counting $count rows ...\n"  );
		$this->reportMessage( "   ... done.\n" );

		$this->reportMessage( "\nChecking and updating ...\n" );

		foreach ( $rows as $row ) {

			if ( $row->smw_title === '_INST' ) {
				continue;
			}

			$namespace = (int)$row->smw_namespace;

			if ( $namespace === SMW_NS_PROPERTY ) {
				try {
					$property = DIProperty::newFromUserLabel( $row->smw_title );
				} catch( \SMW\Exception\PropertyLabelNotResolvedException $e ) {
					continue;
				}
				$subject = $property->getCanonicalDiWikiPage();
			} else {
				$subject = new DIWikiPage(
					$row->smw_title,
					$namespace,
					$row->smw_iw,
					$row->smw_subobject
				);
			}

			$this->reportMessage(
				$this->progress( $row->smw_id, $i++, $count )
			);

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

		$this->reportMessage( "\n   ... found document(s) ..." );
		$this->reportMessage( "\n" . sprintf( "%-50s%s", "       ... missing ...", $notExists_count ) );
		$this->reportMessage( "\n" . sprintf( "%-50s%s", "       ... divergent revision/date ...", $dataDiff_count ) );
		$this->reportMessage( "\n" . sprintf( "%-50s%s", "   ... added update job(s) ...", ( $notExists_count + $dataDiff_count ) ) );
		$this->reportMessage( "\n" );
	}

	private function runJob( $title ) {

		$job = $this->jobFactory->newUpdateJob(
			$title
		);

		$job->run();
	}

	private function progress( $id, $i, $count ) {
		return
			"\r". sprintf( "%-49s%s", "   ... checking entity", sprintf( "%4.0f%% (%s/%s)", min( 100, round( ( $i / $count ) * 100 ) ), $id, $this->lastId ) );
	}

}

$maintClass = 'SMW\Maintenance\RebuildElasticMissingDocuments';
require_once( RUN_MAINTENANCE_IF_MAIN );
