<?php

namespace SMW\SQLStore;

use Hooks;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\CompatibilityMode;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\SQLStore\TableBuilder\TableSchemaManager;
use SMW\SQLStore\TableBuilder\TableBuildExaminer;
use SMW\Options;
use SMW\Site;
use SMW\TypesRegistry;
use SMW\SetupFile;

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
	const OPT_IMPORT = 'installer.import';

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
	 * @var Options
	 */
	private $options;

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @since 2.5
	 *
	 * @param TableSchemaManager $tableSchemaManager
	 * @param TableBuilder $tableBuilder
	 * @param TableBuildExaminer $tableBuildExaminer
	 */
	public function __construct( TableSchemaManager $tableSchemaManager, TableBuilder $tableBuilder, TableBuildExaminer $tableBuildExaminer ) {
		$this->tableSchemaManager = $tableSchemaManager;
		$this->tableBuilder = $tableBuilder;
		$this->tableBuildExaminer = $tableBuildExaminer;
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
	 * @param boolean $verbose
	 */
	public function install( $verbose = true ) {

		if ( $verbose instanceof Options ) {
			$this->options = $verbose;
		}

		// If for some reason the enableSemantics was not yet enabled
		// still allow to run the tables create in order for the
		// setup to be completed
		if ( CompatibilityMode::extensionNotEnabled() ) {
			CompatibilityMode::enableTemporaryCliUpdateMode();
		}

		$executionTimes = [];
		$startTime = microtime( true );

		$this->setupFile->setMaintenanceMode( true );

		$messageReporter = $this->newMessageReporter(
			$this->options->has( 'verbose' ) && $this->options->get( 'verbose' )
		);

		$messageReporter->reportMessage( "\nStorage engine: \"SMWSQLStore3\" (or an extension thereof)\n" );
		$messageReporter->reportMessage( "\nSetting up the database tables ...\n\n" );

		$this->tableBuilder->setMessageReporter(
			$messageReporter
		);

		$this->tableBuildExaminer->setMessageReporter(
			$messageReporter
		);

		// #3559
		$tables = $this->tableSchemaManager->getTables();
		$this->setupFile->setMaintenanceMode( [ 'create-tables' => 20 ] );

		Hooks::run(
			'SMW::SQLStore::Installer::BeforeCreateTablesComplete',
			[
				$tables,
				$messageReporter
			]
		);

		foreach ( $tables as $table ) {
			$this->tableBuilder->create( $table );
		}

		$executionTimes['create-tables'] = microtime( true );
		$messageReporter->reportMessage( "\nPost-creation examination tasks ...\n\n" );

		$this->setupFile->setMaintenanceMode( [ 'post-creation' => 40 ] );
		$this->tableBuildExaminer->checkOnPostCreation( $this->tableBuilder );
		$executionTimes['post-creation-check'] = microtime( true );

		$this->setupFile->setMaintenanceMode( [ 'table-optimization' => 60 ] );
		$this->runTableOptimization( $messageReporter );
		$executionTimes['table-optimization'] = microtime( true );

		$this->setupFile->setMaintenanceMode( [ 'supplement-jobs' => 80 ] );
		$this->addSupplementJobs( $messageReporter );
		$executionTimes['supplement-jobs'] = microtime( true );

		$this->setupFile->finalize();
		$this->options->set( 'hook-execution', [] );

		Hooks::run(
			'SMW::SQLStore::Installer::AfterCreateTablesComplete',
			[
				$this->tableBuilder,
				$messageReporter,
				$this->options
			]
		);

		if ( ( $hook = $this->options->get( 'hook-execution' ) ) !== [] ) {
			$executionTimes['hook-execution (' . implode( ',', $hook ) . ')'] = microtime( true );
		} else {
			$executionTimes['hook-execution'] = microtime( true );
		}

		$messageReporter->reportMessage( "\nDatabase and table setup completed ...\n" );
		$this->outputReport( $messageReporter, $startTime, $executionTimes );

		$messageReporter->reportMessage( "   ... done.\n" );

		// Add an extra space when executed as part of the `update.php`
		if ( defined( 'MW_UPDATER' ) ) {
			$messageReporter->reportMessage( "\n" );
		}

		return true;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $verbose
	 */
	public function uninstall( $verbose = true ) {

		$messageReporter = $this->newMessageReporter( $verbose );

		$messageReporter->reportMessage( "\nStorage engine: \"SMWSQLStore3\" (or an extension thereof)\n" );
		$messageReporter->reportMessage( "\nDeleting database tables (generated by SMW) ...\n" );

		$this->tableBuilder->setMessageReporter(
			$messageReporter
		);

		foreach ( $this->tableSchemaManager->getTables() as $table ) {
			$this->tableBuilder->drop( $table );
		}

		$messageReporter->reportMessage( "   ... done.\n" );
		$this->tableBuildExaminer->checkOnPostDestruction( $this->tableBuilder );

		Hooks::run(
			'SMW::SQLStore::Installer::AfterDropTablesComplete',
			[
				$this->tableBuilder,
				$messageReporter,
				$this->options
			]
		);

		$messageReporter->reportMessage( "\nStandard and auxiliary tables with all corresponding data\n" );
		$messageReporter->reportMessage( "have been removed successfully.\n" );

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

	private function newMessageReporter( $verbose = true ) {

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

		return $messageReporter;
	}

	private function runTableOptimization( $messageReporter ) {

		$messageReporter->reportMessage( "\nTable optimization task ...\n" );

		if ( !$this->options->safeGet( self::OPT_TABLE_OPTIMIZE, false ) ) {
			return $messageReporter->reportMessage( "   ... skipping the table optimization\n" );
		}

		$messageReporter->reportMessage( "\n" );

		foreach ( $this->tableSchemaManager->getTables() as $table ) {
			$this->tableBuilder->optimize( $table );
		}

		$messageReporter->reportMessage( "\nOptimization completed.\n" );
	}

	private function addSupplementJobs( $messageReporter ) {

		if ( !$this->options->safeGet( self::OPT_SUPPLEMENT_JOBS, false ) ) {
			return $messageReporter->reportMessage( "\nSkipping supplement job creation.\n" );
		}

		$messageReporter->reportMessage( "\nAdding supplement jobs ...\n" );
		$messageReporter->reportMessage( "   ... property statistics rebuild job ...\n" );

		$title = \Title::newFromText( 'SMW\SQLStore\Installer' );

		$job = new PropertyStatisticsRebuildJob(
			$title,
			PropertyStatisticsRebuildJob::newRootJobParams( 'smw.propertyStatisticsRebuild', $title ) + [ 'waitOnCommandLine' => true ]
		);

		$job->insert();

		$messageReporter->reportMessage( "   ... entity disposer job ...\n" );

		$job = new EntityIdDisposerJob(
			$title,
			EntityIdDisposerJob::newRootJobParams( 'smw.entityIdDisposer', $title ) + [ 'waitOnCommandLine' => true ]
		);

		$job->insert();

		$messageReporter->reportMessage( "   ... done.\n" );
	}

	private function outputReport( $messageReporter, $startTime, $executionTimes ) {

		$len = 67 - strlen( MW_VERSION );

		$messageReporter->reportMessage(
			sprintf( "%-{$len}s%s\n", "   ... MediaWiki", MW_VERSION )
		);

		$len = 67 - strlen( SMW_VERSION );

		$messageReporter->reportMessage(
			sprintf( "%-{$len}s%s\n", "   ... Semantic MediaWiki", SMW_VERSION )
		);

		$messageReporter->reportMessage( "   ... Execution report ...\n" );

		foreach ( $executionTimes as $key => $time ) {
			$t = $time - $startTime;

			if ( $t > 60 ) {
				$t = sprintf( "%.2f", $t / 60 );
				$unit = 'min';
			} else {
				$t = sprintf( "%.2f", $t );
				$unit = 'sec';
			}

			$len = 48 - strlen( $t );
			$placeholderLen = $len - strlen( $key );

			$messageReporter->reportMessage(
				sprintf( "%-{$len}s%s\n", "       ... $key " . sprintf( "%'.{$placeholderLen}s", ' ' ), $t . " ($unit.)" )
			);

			$startTime = $time;
		}
	}

}
