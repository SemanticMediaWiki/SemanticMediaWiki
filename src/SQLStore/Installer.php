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

		$cliMsgFormatter = new CliMsgFormatter();
		$timer = new Timer();

		$timer->keys = [
			'create-tables' => 'Create tables',
			'post-creation' => 'Post-creation examination',
			'table-optimization' => 'Table optimization',
			'supplement-jobs' => 'Supplement jobs',
			'hook-execution' => 'AfterCreateTablesComplete (Hook)'
		];

		$timer->new( 'create-tables' );

		$this->setupFile->setMaintenanceMode( true );
		$this->setupFile->setLatestVersion( SMW_VERSION );

		$messageReporter = $this->newMessageReporter(
			$this->options->has( 'verbose' ) && $this->options->get( 'verbose' )
		);

		if (
			$this->options->has( SMW_EXTENSION_SCHEMA_UPDATER ) &&
			$this->options->get( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
			$messageReporter->reportMessage( $cliMsgFormatter->section( 'Sematic MediaWiki', 3, '=' ) );
			$messageReporter->reportMessage( "\n" . $cliMsgFormatter->head() );
			$this->options->set( SMW_EXTENSION_SCHEMA_UPDATER, false );
		}

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Database setup' )
		);

		$messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->twoCols( 'Storage engine:', 'SMWSQLStore3' )
		);

		$this->tableBuilder->setMessageReporter(
			$messageReporter
		);

		$this->tableBuildExaminer->setMessageReporter(
			$messageReporter
		);

		if ( $this->meetsVersionMinRequirement( $messageReporter, Setup::MINIMUM_DB_VERSION ) === false ) {
			return true;
		}

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

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Core tables', 6, '-', true ) . "\n"
		);

		$custom = false;

		foreach ( $tables as $table ) {

			if ( !$custom && !$table->isCoreTable() ) {
				$custom = $cliMsgFormatter->section( 'Custom tables', 6, '-', true ) . "\n";

				$messageReporter->reportMessage(
					$custom
				);
			}

			$this->tableBuilder->create( $table );
		}

		$timer->stop( 'create-tables' )->new( 'post-creation' );

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Post-creation examination' ) . "\n"
		);

		$this->setupFile->setMaintenanceMode( [ 'post-creation' => 40 ] );
		$this->tableBuildExaminer->checkOnPostCreation( $this->tableBuilder );

		$timer->stop( 'post-creation' )->new( 'table-optimization' );

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Table optimization' ) . "\n"
		);

		$this->setupFile->setMaintenanceMode( [ 'table-optimization' => 60 ] );
		$this->runTableOptimization( $messageReporter );

		$timer->stop( 'table-optimization' )->new( 'supplement-jobs' );

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Supplement jobs' ) . "\n"
		);

		$this->setupFile->setMaintenanceMode( [ 'supplement-jobs' => 80 ] );
		$this->addSupplementJobs( $messageReporter );

		$this->setupFile->finalize();

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'AfterCreateTablesComplete (Hook)' )
		);

		$timer->stop( 'supplement-jobs' )->new( 'hook-execution' );

		$this->options->set( 'hook-execution', [] );

		Hooks::run(
			'SMW::SQLStore::Installer::AfterCreateTablesComplete',
			[
				$this->tableBuilder,
				$messageReporter,
				$this->options
			]
		);

		$timer->stop( 'hook-execution' );

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Summary' ) . "\n"
		);

		$this->outputReport( $messageReporter, $timer );

		if ( $this->options->has( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
			$messageReporter->reportMessage( $cliMsgFormatter->section( '', 0, '=' ) );
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

		$cliMsgFormatter = new CliMsgFormatter();

		$messageReporter = $this->newMessageReporter( $verbose );

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Database table cleanup' )
		);

		$messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->twoCols( 'Storage engine:', 'SMWSQLStore3' )
		);

		$messageReporter->reportMessage( "\nSemantic MediaWiki related tables ...\n" );

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

		$text = [
			'Standard and auxiliary tables with all corresponding data',
			'have been removed successfully.'
		];

		$messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
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

		if ( !$this->options->safeGet( self::OPT_TABLE_OPTIMIZE, false ) ) {
			return $messageReporter->reportMessage( "   ... skipping the table optimization\n" );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$text = [
			'The optimization task can take a moment to complete and depending',
			'on the database backend, tables can be locked during the operation.'
		];

		$messageReporter->reportMessage(
			$cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$messageReporter->reportMessage( "\nChecking table ...\n" );

		foreach ( $this->tableSchemaManager->getTables() as $table ) {
			$this->tableBuilder->optimize( $table );
		}

		$messageReporter->reportMessage( "   ... done.\n" );
	}

	private function addSupplementJobs( $messageReporter ) {

		$cliMsgFormatter = new CliMsgFormatter();

		if ( !$this->options->safeGet( self::OPT_SUPPLEMENT_JOBS, false ) ) {
			return $messageReporter->reportMessage( "\nSkipping supplement job creation.\n" );
		}

		$messageReporter->reportMessage(
			$cliMsgFormatter->oneCol( "Adding jobs ..." )
		);

		$messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( "... Property statistics rebuild job ...", 3 )
		);

		$title = \Title::newFromText( 'SMW\SQLStore\Installer' );

		$propertyStatisticsRebuildJob = new PropertyStatisticsRebuildJob(
			$title,
			PropertyStatisticsRebuildJob::newRootJobParams( 'smw.propertyStatisticsRebuild', $title ) + [ 'waitOnCommandLine' => true ]
		);

		$propertyStatisticsRebuildJob->insert();

		$messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( "... Entity disposer job ...", 3 )
		);

		$entityIdDisposerJob = new EntityIdDisposerJob(
			$title,
			EntityIdDisposerJob::newRootJobParams( 'smw.entityIdDisposer', $title ) + [ 'waitOnCommandLine' => true ]
		);

		$entityIdDisposerJob->insert();

		$messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$messageReporter->reportMessage( "   ... done.\n" );
	}

	private function outputReport( $messageReporter, $timer ) {

		$cliMsgFormatter = new CliMsgFormatter();
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

			$messageReporter->reportMessage(
				$cliMsgFormatter->twoCols( "- $key", "$t $unit", 0, '.' )
			);
		}
	}

	private function meetsVersionMinRequirement( $messageReporter, $version ) {

		$requirements = $this->tableBuildExaminer->defineDatabaseRequirements(
			$version
		);

		if ( $this->tableBuildExaminer->meetsMinimumRequirement( $requirements ) ) {
			return $this->setupFile->remove( SetupFile::DB_REQUIREMENTS );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Compatibility notice' )
		);

		$text = [
			"The {$requirements['type']} database version of {$requirements['latest_version']}",
			"doesn't meet the minimum requirement of {$requirements['minimum_version']}",
			"for Semantic MediaWiki.",
			"\n\n",
			 "The installation of Semantic MediaWiki was aborted!"
		];

		$messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->setupFile->set( [ SetupFile::DB_REQUIREMENTS => $requirements ] );
		$this->setupFile->finalize();

		if ( $this->options->has( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
			$messageReporter->reportMessage( $cliMsgFormatter->section( '', 0, '=' ) );
			$messageReporter->reportMessage( "\n" );
		}

		return false;
	}

}
