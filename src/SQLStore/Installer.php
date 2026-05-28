<?php

namespace SMW\SQLStore;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\JobQueue\JobFactory;
use MediaWiki\Title\TitleFactory;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Onoi\MessageReporter\MessageReporterFactory;
use RuntimeException;
use SMW\MediaWiki\Job;
use SMW\Options;
use SMW\Setup;
use SMW\Setup\MigrateSmwJsonToDb;
use SMW\SetupFile;
use SMW\SQLStore\Installer\TableOptimizer;
use SMW\SQLStore\Installer\VersionExaminer;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\SQLStore\TableBuilder\TableBuildExaminer;
use SMW\SQLStore\TableBuilder\TableSchemaManager;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\Timer;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class Installer implements MessageReporter {

	use MessageReporterAwareTrait;

	private ?HookContainer $hookContainer = null;

	/**
	 * @since 7.0.0
	 */
	public function setHookContainer( HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
	}

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

	private Options $options;

	private SetupFile $setupFile;

	private ?CliMsgFormatter $cliMsgFormatter = null;

	/**
	 * @since 2.5
	 */
	public function __construct(
		private TableSchemaManager $tableSchemaManager,
		private TableBuilder $tableBuilder,
		private TableBuildExaminer $tableBuildExaminer,
		private VersionExaminer $versionExaminer,
		private TableOptimizer $tableOptimizer,
		private readonly TitleFactory $titleFactory,
		private readonly JobFactory $jobFactory,
	) {
		$this->options = new Options();
		$this->setupFile = new SetupFile();
	}

	/**
	 * @since 3.0
	 *
	 * @param Options|array $options
	 */
	public function setOptions( $options ): void {
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
	public function setSetupFile( SetupFile $setupFile ): void {
		$this->setupFile = $setupFile;
	}

	/**
	 * @since 2.5
	 *
	 * @param Options|bool $verbose
	 */
	public function install( $verbose = true ): bool {
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
			"\n" . $this->cliMsgFormatter->twoCols( 'Storage engine:', 'SQLStore' )
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

		if ( !$this->versionExaminer->meetsVersionMinRequirement( Setup::MINIMUM_DB_VERSION ) ) {
			return $this->printBottom();
		}

		// #3559
		$tables = $this->tableSchemaManager->getTables();

		// Run data migrations that must complete before column types change
		$this->tableBuildExaminer->runPreCreationMigrations();

		$this->setupFile->setMaintenanceMode( [ 'create-tables' => 20 ] );

		$this->hookContainer->run(
			'SMW::SQLStore::Installer::BeforeCreateTablesComplete',
			[ $tables, $this->messageReporter ]
		);

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

		// Mark a legacy `.smw.json` consumed. Data transfer happened
		// earlier in the request via `SetupFile::loadSchema`'s legacy
		// fallback (it hydrated `$GLOBALS` from the file) combined with
		// the install pipeline's normal merge-then-save writes, so by
		// this point `smw_meta` already reflects the user's state. The
		// rename is the consumed-marker; the next upgrade short-circuits
		// at file presence.
		MigrateSmwJsonToDb::run( $this->messageReporter );

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

		$this->hookContainer->run(
			'SMW::SQLStore::Installer::AfterCreateTablesComplete',
			[ $this->tableBuilder, $this->messageReporter, $this->options ]
		);

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
	 * @param bool $verbose
	 */
	public function uninstall( $verbose = true ): bool {
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

		$this->hookContainer->run(
			'SMW::SQLStore::Installer::AfterDropTablesComplete',
			[ $this->tableBuilder, $this->messageReporter, $this->options ]
		);

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
	public function reportMessage( $message ): void {
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

	/**
	 * @throws RuntimeException
	 */
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

		$title = $this->titleFactory->newFromText( 'SMW\SQLStore\Installer' );

		if ( $title === null ) {
			throw new RuntimeException(
				'Failed to create SMW\SQLStore\Installer title.'
			);
		}

		/** @var Job $propertyStatisticsRebuildJob */
		$propertyStatisticsRebuildJob = $this->jobFactory->newJob(
			'smw.propertyStatisticsRebuild',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ]
				+ Job::newRootJobParams( 'smw.propertyStatisticsRebuild', $title )
				+ [ 'waitOnCommandLine' => true ]
		);

		$propertyStatisticsRebuildJob->insert();

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( "... Entity disposer job ...", 3 )
		);

		/** @var Job $entityIdDisposerJob */
		$entityIdDisposerJob = $this->jobFactory->newJob(
			'smw.entityIdDisposer',
			[ 'namespace' => $title->getNamespace(), 'title' => $title->getDBkey() ]
				+ Job::newRootJobParams( 'smw.entityIdDisposer', $title )
				+ [ 'waitOnCommandLine' => true ]
		);

		$entityIdDisposerJob->insert();

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function outputReport( Timer $timer ): void {
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

	private function printHead(): void {
		if (
			$this->options->has( SMW_EXTENSION_SCHEMA_UPDATER ) &&
			$this->options->get( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
			$this->messageReporter->reportMessage( $this->cliMsgFormatter->section( 'Semantic MediaWiki', 3, '=' ) );
			$this->messageReporter->reportMessage( "\n" . $this->cliMsgFormatter->head() );
			$this->options->set( SMW_EXTENSION_SCHEMA_UPDATER, false );
		}
	}

	private function printBottom(): bool {
		if ( $this->options->has( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
			$this->messageReporter->reportMessage( $this->cliMsgFormatter->section( '', 0, '=' ) );
			$this->messageReporter->reportMessage( "\n" );
		}

		return true;
	}

}
