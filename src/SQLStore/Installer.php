<?php

namespace SMW\SQLStore;

use Hooks;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\SQLStore\TableBuilder\TableSchemaManager;
use SMW\SQLStore\TableBuilder\TableBuildExaminer;
use SMW\SQLStore\Installer\VersionExaminer;
use SMW\SQLStore\Installer\TableOptimizer;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use SMW\Options;
use SMW\Site;
use SMW\TypesRegistry;
use SMW\SetupFile;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\Timer;
use SMW\Setup;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Installer implements MessageReporter {

	use MessageReporterAwareTrait;
	use HookDispatcherAwareTrait;

	/**
	 * Optimize option
	 */
	const OPT_TABLE_OPTIMIZE = 'installer.table.optimize';

	/**
	 * Job option
	 */
	const OPT_SUPPLEMENT_JOBS = 'installer.supplement.jobs';

	/**
	 * Import option
	 */
	const RUN_IMPORT = 'installer/import';

	/**
	 * `smw_hash` field population
	 */
	const POPULATE_HASH_FIELD_COMPLETE = 'populate.smw_hash_field_complete';

	/**
	 * @var TableSchemaManager
	 */
	private $tableSchemaManager;

	/**
	 * @var TableBuilder
	 */
	private $tableBuilder;

	/**
	 * @var TableBuildExaminer
	 */
	private $tableBuildExaminer;

	/**
	 * @var VersionExaminer
	 */
	private $versionExaminer;

	/**
	 * @var TableOptimizer
	 */
	private $tableOptimizer;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

	/**
	 * @since 2.5
	 *
	 * @param TableSchemaManager $tableSchemaManager
	 * @param TableBuilder $tableBuilder
	 * @param TableBuildExaminer $tableBuildExaminer
	 * @param VersionExaminer VersionExaminer
	 * @param TableOptimizer $tableOptimizer
	 */
	public function __construct( TableSchemaManager $tableSchemaManager, TableBuilder $tableBuilder, TableBuildExaminer $tableBuildExaminer, VersionExaminer $versionExaminer, TableOptimizer $tableOptimizer ) {
		$this->tableSchemaManager = $tableSchemaManager;
		$this->tableBuilder = $tableBuilder;
		$this->tableBuildExaminer = $tableBuildExaminer;
		$this->versionExaminer = $versionExaminer;
		$this->tableOptimizer = $tableOptimizer;
		$this->options = new Options();
		$this->setupFile = new SetupFile();
	}

	/**
	 * @since 3.0
	 *
	 * @param Options|array $options
	 */
	public function setOptions( $options ) {

		if ( !$options instanceof Options ) {
			$options = new Options( $options );
		}

		$this->options = $options;
	}

	/**
	 * @since 3.1
	 *
	 * @param SetupFile $setupFile
	 */
	public function setSetupFile( SetupFile $setupFile ) {
		$this->setupFile = $setupFile;
	}

	/**
	 * @since 2.5
	 *
	 * @param Options|boolean $verbose
	 */
	public function install( $verbose = true ) {

		if ( $verbose instanceof Options ) {
			$this->options = $verbose;
		}

		$this->cliMsgFormatter = new CliMsgFormatter();
		$timer = new Timer();

		$timer->keys = [
			'create-tables' => 'Create (or checking) table(s)',
			'post-creation' => 'Post-creation examination',
			'table-optimization' => 'Table optimization',
			'supplement-jobs' => 'Supplement jobs',
			'hook-execution' => 'AfterCreateTablesComplete (Hook)'
		];

		$timer->new( 'create-tables' );

		$this->setupFile->setMaintenanceMode( true );
		$this->setupFile->setLatestVersion( SMW_VERSION );

		$this->initMessageReporter(
			$this->options->has( 'verbose' ) && $this->options->get( 'verbose' )
		);

		$this->printHead();

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Database setup' )
		);

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->twoCols( 'Storage engine:', 'SMWSQLStore3' )
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( 'Database (type/version):', $this->tableBuildExaminer->getDatabaseInfo() )
		);

		$this->tableBuilder->setMessageReporter(
			$this->messageReporter
		);

		$this->tableBuildExaminer->setMessageReporter(
			$this->messageReporter
		);

		$this->versionExaminer->setMessageReporter(
			$this->messageReporter
		);

		$this->tableOptimizer->setMessageReporter(
			$this->messageReporter
		);

		if ( $this->versionExaminer->meetsVersionMinRequirement( Setup::MINIMUM_DB_VERSION ) === false ) {
			return $this->printBottom();
		}

		// #3559
		$tables = $this->tableSchemaManager->getTables();
		$this->setupFile->setMaintenanceMode( [ 'create-tables' => 20 ] );

		/**
		 * @see HookDispatcher::onInstallerBeforeCreateTablesComplete
		 */
		$this->hookDispatcher->onInstallerBeforeCreateTablesComplete( $tables, $this->messageReporter );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Core table(s)', 6, '-', true ) . "\n"
		);

		$custom = false;

		foreach ( $tables as $table ) {

			if ( !$custom && !$table->isCoreTable() ) {
				$custom = $this->cliMsgFormatter->section( 'Custom table(s)', 6, '-', true ) . "\n";

				$this->messageReporter->reportMessage(
					$custom
				);
			}

			$this->tableBuilder->create( $table );
		}

		$timer->stop( 'create-tables' )->new( 'post-creation' );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Post-creation examination' ) . "\n"
		);

		$this->setupFile->setMaintenanceMode( [ 'post-creation' => 40 ] );
		$this->tableBuildExaminer->checkOnPostCreation( $this->tableBuilder );

		$timer->stop( 'post-creation' )->new( 'table-optimization' );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Table optimization' ) . "\n"
		);

		$this->setupFile->setMaintenanceMode( [ 'table-optimization' => 60 ] );
		$this->runTableOptimization();

		$timer->stop( 'table-optimization' )->new( 'supplement-jobs' );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Supplement jobs' ) . "\n"
		);

		$this->setupFile->setMaintenanceMode( [ 'supplement-jobs' => 80 ] );
		$this->addSupplementJobs();

		$this->setupFile->finalize();

		$timer->stop( 'supplement-jobs' )->new( 'hook-execution' );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'AfterCreateTablesComplete (Hook)' )
		);

		$text = [
			'Task(s) registered via the hook depend on the functionality implemented',
			'and may take a moment to complete.'
		];

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		/**
		 * @see HookDispatcher::onInstallerAfterCreateTablesComplete
		 */
		$this->hookDispatcher->onInstallerAfterCreateTablesComplete( $this->tableBuilder, $this->messageReporter, $this->options );

		$timer->stop( 'hook-execution' );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Summary' ) . "\n"
		);

		$this->outputReport( $timer );
		$this->printBottom();

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $verbose
	 */
	public function uninstall( $verbose = true ) {

		$this->cliMsgFormatter = new CliMsgFormatter();

		$this->initMessageReporter( $verbose );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Database table cleanup' )
		);

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->twoCols( 'Storage engine:', 'SMWSQLStore3' )
		);

		$this->messageReporter->reportMessage( "\nSemantic MediaWiki related tables ...\n" );

		$this->tableBuilder->setMessageReporter(
			$this->messageReporter
		);

		foreach ( $this->tableSchemaManager->getTables() as $table ) {
			$this->tableBuilder->drop( $table );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
		$this->tableBuildExaminer->checkOnPostDestruction( $this->tableBuilder );

		/**
		 * @see HookDispatcher::onInstallerAfterDropTablesComplete
		 */
		$this->hookDispatcher->onInstallerAfterDropTablesComplete( $this->tableBuilder, $this->messageReporter, $this->options );

		$text = [
			'Standard and auxiliary tables with all corresponding data',
			'have been removed successfully.'
		];

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->setupFile->reset();

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		ob_start();
		print $message;
		ob_flush();
		flush();
		ob_end_clean();
	}

	private function initMessageReporter( $verbose = true ) {

		if ( $this->messageReporter !== null ) {
			return $this->messageReporter;
		}

		$messageReporterFactory = MessageReporterFactory::getInstance();

		if ( !$verbose ) {
			$messageReporter = $messageReporterFactory->newNullMessageReporter();
		} else {
			$messageReporter = $messageReporterFactory->newObservableMessageReporter();
			$messageReporter->registerReporterCallback( [ $this, 'reportMessage' ] );
		}

		$this->setMessageReporter( $messageReporter );
	}

	private function runTableOptimization() {

		if ( !$this->options->safeGet( self::OPT_TABLE_OPTIMIZE, false ) ) {
			return $this->messageReporter->reportMessage(
				"Table optimization was not enabled (or skipped), stopping the task.\n"
			);
		}

		$this->tableOptimizer->runForTables(
			$this->tableSchemaManager->getTables()
		);
	}

	private function addSupplementJobs() {

		$this->cliMsgFormatter = new CliMsgFormatter();

		if ( !$this->options->safeGet( self::OPT_SUPPLEMENT_JOBS, false ) ) {
			return $this->messageReporter->reportMessage( "\nSkipping supplement job creation.\n" );
		}

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->oneCol( "Adding jobs ..." )
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( "... Property statistics rebuild job ...", 3 )
		);

		$title = \Title::newFromText( 'SMW\SQLStore\Installer' );

		$propertyStatisticsRebuildJob = new PropertyStatisticsRebuildJob(
			$title,
			PropertyStatisticsRebuildJob::newRootJobParams( 'smw.propertyStatisticsRebuild', $title ) + [ 'waitOnCommandLine' => true ]
		);

		$propertyStatisticsRebuildJob->insert();

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( "... Entity disposer job ...", 3 )
		);

		$entityIdDisposerJob = new EntityIdDisposerJob(
			$title,
			EntityIdDisposerJob::newRootJobParams( 'smw.entityIdDisposer', $title ) + [ 'waitOnCommandLine' => true ]
		);

		$entityIdDisposerJob->insert();

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function outputReport( $timer ) {

		$this->cliMsgFormatter = new CliMsgFormatter();
		$keys = $timer->keys;

		foreach ( $timer->getTimes() as $key => $time ) {
			$t = $time;

			if ( $t > 60 ) {
				$t = sprintf( "%.2f", $t / 60 );
				$unit = 'min';
			} else {
				$t = sprintf( "%.2f", $t );
				$unit = 'sec';
			}

			$key = $keys[$key];

			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->twoCols( "- $key", "$t $unit", 0, '.' )
			);
		}
	}

	private function printHead() {

		if (
			$this->options->has( SMW_EXTENSION_SCHEMA_UPDATER ) &&
			$this->options->get( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
			$this->messageReporter->reportMessage( $this->cliMsgFormatter->section( 'Semantic MediaWiki', 3, '=' ) );
			$this->messageReporter->reportMessage( "\n" . $this->cliMsgFormatter->head() );
			$this->options->set( SMW_EXTENSION_SCHEMA_UPDATER, false );
		}
	}

	private function printBottom() {

		if ( $this->options->has( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
			$this->messageReporter->reportMessage( $this->cliMsgFormatter->section( '', 0, '=' ) );
			$this->messageReporter->reportMessage( "\n" );
		}

		return true;
	}

}
