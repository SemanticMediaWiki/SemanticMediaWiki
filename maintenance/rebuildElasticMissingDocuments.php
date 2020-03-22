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
use SMW\Elastic\Indexer\Replication\ReplicationError;
use SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer;
use SMW\Elastic\Jobs\FileIngestJob;

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
class rebuildElasticMissingDocuments extends \Maintenance {

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
	 * @var []
	 */
	private $missingDocuments = [];

	/**
	 * @since 3.1
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription(
			"Find missing entities (aka. documents) in Elasticsearch and schedule appropriate update jobs."
		);

		$this->addOption( 'namespace', 'Only check entities in the selected namespace. Example: --namespace="NS_MAIN"', false, false );
		$this->addOption( 'check-file-attachment', 'If file ingestion is enabled, provide means to check for the `File attachment` property', false, false );
		$this->addOption( 'id', 'Only check for a particular ID. Example: --id=42', false, false );
		$this->addOption( 'v', 'Be verbose about the progress', false );
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

		if ( !$this->store->getConnection( 'elastic' )->ping() ) {

			$this->reportMessage(
				$cliMsgFormatter->section( 'Compatibility notice' )
			);

			$this->reportMessage(
				"\n" . 'Elasticsearch endpoint(s) are not available!' . "\n"
			);

			return true;
		}

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

		$errorCount = $this->checkAndRebuild( $rows );

		$this->reportMessage(
			"\n" . $cliMsgFormatter->firstCol( "... removed replication trail", 3 )
		);

		$replicationCheck = $elasticFactory->newReplicationCheck(
			$this->store
		);

		$replicationCheck->deleteEntireReplicationTrail();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->reportMessage( "   ... done.\n" );

		$this->reportMessage(
			$cliMsgFormatter->section( "Summary" ) . "\n"
		);

		foreach ( $errorCount as $t => $count ) {
			$this->reportMessage( $cliMsgFormatter->twoCols( "Type ($t)", $count, 0, '.' ) );
		}

		if ( $this->missingDocuments !== [] ) {
			foreach ( $this->missingDocuments as $key => $missingDocuments ) {
				$this->reportMessage( $cliMsgFormatter->section( $key, 3, '-', true ) . "\n" );

				foreach ( $missingDocuments as $missingDocument ) {
					$this->reportMessage( $cliMsgFormatter->oneCol( "- $missingDocument", 0 ) );
				}
			}
		}

		if ( array_sum( $errorCount ) == 0 ) {
			$this->reportMessage(
				$cliMsgFormatter->twoCols( "Total missing", 0, 0, '.' )
			);
		}

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

		if ( $this->hasOption( 'id' ) ) {
			$conditions = [
				"smw_id" => (int)$this->getOption( 'id' )
			];
		} elseif ( $this->hasOption( 'namespace' ) ) {
			$conditions = [
				"smw_namespace" => constant( $this->getOption( 'namespace' ) ),
				"smw_subobject=''",
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWINTDEFIW ),
			];
		} else {
			$conditions = [
				"smw_subobject=''",
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWINTDEFIW ),
			];
		}

		return $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject',
				'smw_rev'
			],
			$conditions,
			__METHOD__,
			[ 'ORDER BY' => 'smw_id' ]
		);
	}

	private function checkAndRebuild( \Iterator $rows ) {

		$cliMsgFormatter = new CliMsgFormatter();
		$connection = $this->store->getConnection( 'mw.db' );

		$count = $rows->numRows();
		$i = 0;
		$errorCount = [];

		if ( $count == 0 ) {
			return $this->reportMessage( "   ... no entities selected ...\n"  );
		}

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... found ...", "$count (rows)", 3 )
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

			if ( $subject === null ) {
				continue;
			}

			$title = $subject->getTitle();

			if ( $title === null ) {
				continue;
			}

			// Check on object and page entity
			$result = $this->documentReplicationExaminer->check(
				$subject,
				[
					DocumentReplicationExaminer::CHECK_DOCUMENT_EXISTS => true,
					DocumentReplicationExaminer::CHECK_MISSING_FILE_ATTACHMENT => $this->getOption( 'check-file-attachment', false )
				]
			);

			if ( !$result instanceof ReplicationError ) {
				continue;
			}

			// If the object isn't a page ($row->smw_rev === null) and it is a
			// `TYPE_MODIFICATION_DATE_MISSING` error reported then skip any update
			// since it is only an object entity that should exists in ES but is
			// allowed to have no modification date as plain object.
			if (
				$result->getType() === ReplicationError::TYPE_MODIFICATION_DATE_MISSING &&
				$row->smw_rev === null ) {
				continue;
			}

			// A redirect may point to an unresolved (red-linked) redirect target
			// which is created as object but doesn't posses any modification date
			if (
				$result->getType() === ReplicationError::TYPE_MODIFICATION_DATE_MISSING &&
				$row->smw_iw === SMW_SQL3_SMWREDIIW ) {
				continue;
			}

			if ( $this->hasOption( 'v' ) ) {
				if ( !isset( $this->missingDocuments[$result->getType()] ) ) {
					$this->missingDocuments[$result->getType()] = [];
				}

				$this->missingDocuments[$result->getType()][] = $subject->getHash();
			}

			if ( !isset( $errorCount[$result->getType()] ) ) {
				$errorCount[$result->getType()] = 0;
			}

			$errorCount[$result->getType()]++;

			if ( $result->getType() === ReplicationError::TYPE_FILE_ATTACHMENT_MISSING ) {
				$job = new FileIngestJob( $title );
			} else {
				$job = $this->jobFactory->newUpdateJob( $title );
			}

			$job->run();
		}

		return $errorCount;
	}

	public function newFromRow( $row ) {

		$namespace = (int)$row->smw_namespace;
		$title = $row->smw_title;

		if ( $namespace === SMW_NS_PROPERTY ) {
			try {
				$property = DIProperty::newFromUserLabel( $row->smw_title );
				$title = str_replace( ' ', '_', $property->getLabel() );
			} catch( PropertyLabelNotResolvedException $e ) {
				return;
			} catch( PredefinedPropertyLabelMismatchException $e ) {
				return;
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

$maintClass = rebuildElasticMissingDocuments::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
