<?php

namespace SMW\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use SMW\DataItems\DataItem;
use SMW\MediaWiki\JobFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Setup;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\PeriodicStatsFlusher;

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
 * @since 2.5
 *
 * @author mwjames
 */
class rebuildFulltextSearchTable extends Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	private $maintenanceLogger;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Rebuild, or optimize, the fulltext search index (only works with SQLStore)';
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
		$this->addOption( 'optimize', 'Run possible table optimization (`OPTIMIZE TABLE`) instead of rebuilding the full-text search index. Support for table optimization depends on the SQL back-end.', false );
		$this->addOption( 'v', 'Show additional (verbose) information about the progress', false );
		$this->addOption( 'quick', 'Suppress abort operation', false );
		// @since 7.3.1: 'n', 's', 'max-time', 'use-job'
		$this->addOption( 'n', 'Batch size. The rebuild will be done in consecutive chunks of this many subject IDs (default ' . SearchTableRebuilder::DEFAULT_BATCH_SIZE . ') instead of rebuilding the index in one pass. The index is not purged, but each chunk replaces its own entries. Can be combined with `-s` and unless `--job-queue` is used, with `--max-time`.', false, true );
		$this->addOption( 's', 'Subject ID (`s_id`) to start with (default 0). If an earlier run was stopped, the value reported by that run can be used to resume the rebuild.', false, true );
		$this->addOption( 'max-time', 'Maximum run time in seconds. The script will not start another chunk after this and reports the subject ID to resume with (`-s`), if any.', false, true );
		$this->addOption( 'use-job', 'Instead of running the rebuild, insert one `smw.fulltextSearchTableRebuild` job into the job queue and return immediately. On each invocation, the job processes one chunk of `-n` subject IDs and re-queues itself for the next one until the rebuild is complete. Process jobs with your job runner, e.g. `php maintenance/run.php runJobs --type=smw.fulltextSearchTableRebuild --maxjobs=500`. Combine `--use-job` with `-n` / `-s` to control the batch size / starting point. Cannot be combined with `--max-time`.', false );
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
	 * @see Maintenance::reportMessage
	 *
	 * @since 2.5
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
		if ( $this->canExecute() !== true ) {
			exit;
		}

		$cliMsgFormatter = new CliMsgFormatter();

		if ( $this->messageReporter === null ) {
			$this->messageReporter = new CallbackMessageReporter( [ $this, 'reportMessage' ] );
		}

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		// General description. Cf. smw-admin-fulltext-intro (i18n)
		$text = [
			"This script is used to rebuild or optimise the search index from property tables that support a full-text search, or defer the rebuild to the job queue.",
			"Any change of the index rules (altered",
			"stopwords, new stemmer etc.) and/or a newly added or altered table",
			"requires running this script again to ensure that the index complies",
			"with the rules set forth by the SQL back-end or TextSanitizer."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();
		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$this->maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildFulltextSearchTableLogger' );
		}

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		// Only the SQLStore is supported
		$searchTableRebuilder = $fulltextSearchTableFactory->newSearchTableRebuilder(
			$applicationFactory->getStore( SQLStore::class )
		);

		$textSanitizer = $fulltextSearchTableFactory->newTextSanitizer();

		$searchTableRebuilder->reportVerbose(
			$this->hasOption( 'v' )
		);

		$searchTableRebuilder->requestOptimization(
			$this->hasOption( 'optimize' )
		);

		if ( !$searchTableRebuilder->canRebuild() ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->section( 'Notice' )
			);

			return $this->messageReporter->reportMessage( "\n" . "Full-text search indexing is not enabled or supported." . "\n" );
		}

		$chunked = $this->hasOption( 'n' ) || $this->hasOption( 's' ) || $this->hasOption( 'max-time' );
		$batchSize = (int)$this->getOption( 'n', $searchTableRebuilder::DEFAULT_BATCH_SIZE );
		$fromSid = (int)$this->getOption( 's', 0 );
		$maxTime = (int)$this->getOption( 'max-time', 0 );
		$useJobQueue = $this->hasOption( 'use-job' );

		// Abort under some conditions
		if ( $chunked && ( $batchSize < 1 || $fromSid < 0 || $maxTime < 0 ) ) {
			$this->maintenanceLogger->logFromArray( [ 'Error' => 'Incorrect parameters' ] );
			$this->fatalError(
				$cliMsgFormatter->wordwrap( [ "`-n` must be at least 1; `-s` and `--max-time` must not be negative." ] )
			);
		}
		if ( $useJobQueue && $this->hasOption( 'max-time' ) ) {
			// @DG should this be fatal?
			$this->maintenanceLogger->logFromArray( [ 'Error' => 'Incorrect parameters' ] );
			$this->fatalError(
				$cliMsgFormatter->wordwrap( [ "Aborted because `--max-time` has no effect with `--use-job`. Omit `--max-time` and consider using `-n` instead to set the window for each queued job." ] )
			);
		}
		if ( $this->hasOption( 'optimize' ) && $useJobQueue ) {
			$this->maintenanceLogger->logFromArray( [ 'Error' => 'Incorrect parameters' ] );
			$this->fatalError(
				$cliMsgFormatter->wordwrap( [ "Aborted because `--optimize` does not support `--use-job`." ] )
			);
		}
		if ( $this->hasOption( 'optimize' ) && $chunked ) {
			$this->maintenanceLogger->logFromArray( [ 'Error' => 'Incorrect parameters' ] );
			$this->fatalError(
				$cliMsgFormatter->wordwrap( [ "Aborted because `--optimize` does not support batch mode parameters." ] )
			);
		}

		// Pre-run reporting

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Setting(s)' )
		);

		$this->reportConfiguration(
			$searchTableRebuilder,
			$textSanitizer
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Rebuild', 3, '-', true )
		);

		if ( $this->hasOption( 'optimize' ) ) {
			// @since 7.3.1
			$text = [
				"The index table is not purged.",
				"This process inserts a single job into the job queue to run table optimization (`OPTIMIZE TABLE`) without touching the index data."
			];
		} elseif ( $useJobQueue ) {
			// @since 7.3.1
			$text = [
				"This process does not purge the index table or execute the rebuild.",
				"What it does instead is insert a single job into the job queue to rebuild one chunk of $batchSize subject IDs and re-queue itself for the next chunk until the rebuild is complete."
			];
		} elseif ( $chunked ) {
			// @since 7.3.1
			$text = [
				"The index table is not purged.",
				"It is rebuilt in chunks of $batchSize subject IDs, with each chunk replacing its own index entries.",
				"The index remains searchable and if a run is interrupted, it can be resumed with `-s`."
			];
		} else {
			// Single-run rebuild
			$text = [
				"The entire index table is going to be purged first.",
				"It may take a moment before the rebuild is completed due to varying table contents."
			];
		}

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		// Possibly defer to job queue
		if ( $useJobQueue ) {
			$this->queueRebuildJob( $applicationFactory->newJobFactory(), $fromSid, $batchSize );
			return true;
		}

		// Countdown
		if ( !$this->hasOption( 'quick' ) ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->countDown( 'Abort the rebuild with CTRL-C in ...', 5 )
			);
		}

		// Further setup
		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		$searchTableRebuilder->setMessageReporter( $this->messageReporter );

		$searchTableRebuilder->setStatsFlusher(
			PeriodicStatsFlusher::newFromGlobalState()
		);

		// Run rebuild (full or chunked) or optimisation

		if ( $chunked ) {
			$result = true;
			$resumeSid = $searchTableRebuilder->rebuildInChunks( $fromSid, $batchSize, $maxTime );
			$this->reportChunkedResult( $resumeSid, $batchSize );
		} else {
			$result = $searchTableRebuilder->rebuild();
		}

		// Post-run reporting

		if ( $this->hasOption( 'report-runtime' ) ) {
			$this->messageReporter->reportMessage( $cliMsgFormatter->section( 'Runtime report' ) );

			$this->messageReporter->reportMessage(
				"\n" . $maintenanceHelper->getFormattedRuntimeValues()
			);
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Action' => $this->hasOption( 'optimize' )
					? 'table optimization'
					: ( $chunked ? 'chunked rebuild' : 'single-run rebuild' ),
				'Memory used' => $runtimeValues['memory-used'],
				'Time used' => $runtimeValues['humanreadable-time']
			];
			if ( $chunked && $resumeSid !== null ) {
				// If rebuild stopped because of --max-time, log
				// the info needed with which to resume.
				$log['Next ID'] = $resumeSid;
				$log['Batch size'] = $batchSize;
			}

			$this->maintenanceLogger->logFromArray( $log );
		}

		$maintenanceHelper->reset();
		return $result;
	}

	private function reportConfiguration( $searchTableRebuilder, $textSanitizer ) {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage( "\n" );

		foreach ( $textSanitizer->getVersions() as $key => $value ) {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols( "- $key", $value )
			);
		}

		$searchTable = $searchTableRebuilder->getSearchTable();
		$indexableDataTypes = [];

		$dataTypes = [
			DataItem::TYPE_BLOB => 'BLOB',
			DataItem::TYPE_URI  => 'URI',
			DataItem::TYPE_WIKIPAGE => 'WIKIPAGE'
		];

		foreach ( $dataTypes as $key => $value ) {
			if ( $searchTable->isValidByType( $key ) ) {
				$indexableDataTypes[] = $value;
			}
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( "- DataTypes (indexable)", implode( ', ', $indexableDataTypes ) )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Exempted propertie(s)', 3, '-', true )
		);

		$text = [
			implode( ', ', $searchTable->getPropertyExemptionList() )
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);
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

	/**
	 * Inserts a single job into the job queue, reports to the
	 * CLI and optionally, writes to the maintenance log.
	 * 
	 * @since 7.3.1
	 */
	private function queueRebuildJob( JobFactory $jobFactory, int $fromSid, int $batchSize ): void {
		// Create a 'dummy' Title. Even if it carries no meaning of its
		// own, it's needed to satisfy the job queue's storage format.
		// Follows the example of other maintenance scripts that
		// insert a job directly, e.g. disposeOutdatedEntities.php.
		$title = $this->getServiceContainer()->getTitleFactory()->newFromText( __CLASS__ );

		$job = $jobFactory->newFulltextSearchTableRebuildJob(
			$title,
			[ 's' => $fromSid, 'n' => $batchSize ]
		);
		$job->setParameter( 'mode', 'chunked' );

		$job->insert();

		$this->messageReporter->reportMessage(
			"\n   ... queued (s=$fromSid, n=$batchSize).\n\n" .
			"   Run it with, for example:\n" .
			"   php maintenance/run.php runJobs --type=smw.fulltextSearchTableRebuild --maxjobs=500\n"
		);

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$this->maintenanceLogger->logFromArray( [
				'Action' => 'delegation to job queue'
			] );
		}
	}

	/**
	 * Reports the result of a chunked rebuild operation.
	 * Does not cover the maintenance log.
	 *
	 * @since 7.3.1
	 *
	 * @param ?int $resumeSid The subject ID to resume from, or null if the rebuild is complete.
	 * @param int $batchSize
	 */
	private function reportChunkedResult( ?int $resumeSid, int $batchSize ): void {
		if ( $resumeSid === null ) {
			$this->messageReporter->reportMessage( "\n   ... done.\n" );
			return;
		}

		$this->messageReporter->reportMessage(
			"\n   ... stopped because of `--max-time`. The rebuild is not complete yet.\n" .
			"   Resume with: -n $batchSize -s $resumeSid\n"
		);
	}

}

// @codeCoverageIgnoreStart
$maintClass = rebuildFulltextSearchTable::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
