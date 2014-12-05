<?php

namespace SMW\Maintenance;

use SMW\Maintenance\DataRebuilder;
use SMW\Reporter\ObservableMessageReporter;
use SMW\StoreFactory;
use SMW\Settings;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Recreates all the semantic data in the database, by cycling through all
 * the pages that might have semantic data, and calling functions that
 * re-save semantic data for each one.
 *
 * Note: if SMW is not installed in its standard path under ./extensions
 *       then the MW_INSTALL_PATH environment variable must be set.
 *       See README in the maintenance directory.
 *
 * Usage:
 * php rebuildData.php [options...]
 *
 * -d <delay>   Wait for this many milliseconds after processing an article, useful for limiting server load.
 * -s <startid> Start refreshing at given article ID, useful for partial refreshing
 * -e <endid>   Stop refreshing at given article ID, useful for partial refreshing
 * -n <numids>  Stop refreshing after processing a given number of IDs, useful for partial refreshing
 * --startidfile <startidfile> Read <startid> from a file instead of the arguments and write the next id
 *              to the file when finished. Useful for continual partial refreshing from cron.
 * -b <backend> Execute the operation for the storage backend of the given name
 *              (default is to use the current backend)
 * -v           Be verbose about the progress.
 * -c           Will refresh only category pages (and other explicitly named namespaces)
 * -p           Will refresh only property pages (and other explicitly named namespaces)
 * -t           Will refresh only type pages (and other explicitly named namespaces)
 * --page=<pagelist> will refresh only the pages of the given names, with | used as a separator.
 *              Example: --page="Page 1|Page 2" refreshes Page 1 and Page 2
 *              Options -s, -e, -n, --startidfile, -c, -p, -t are ignored if --page is given.
 * --query=<query> Will refresh only pages returned by a given query.
 *              Example: --query='[[Category:SomeCategory]]'
 * -f           Fully delete all content instead of just refreshing relevant entries. This will also
 *              rebuild the whole storage structure. May leave the wiki temporarily incomplete.
 * --server=<server> The protocol and server name to as base URLs, e.g.
 *              http://en.wikipedia.org. This is sometimes necessary because
 *              server name detection may fail in command line scripts.
 *
 * @author Yaron Koren
 * @author Markus Krötzsch
 */
class RebuildData extends \Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( "\n" .
			"Recreates all the semantic data in the database, by cycling through all \n" .
			"the pages that might have semantic data, and calling functions that \n" .
			"re-save semantic data for each one. \n"
		);

		$this->addDefaultParams();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 */
	protected function addDefaultParams() {

		$this->addOption( 'd', '<delay> Wait for this many milliseconds after processing an article, useful for limiting server load.', false, true );
		$this->addOption( 's', '<startid> Start refreshing at given article ID, useful for partial refreshing.', false, true );
		$this->addOption( 'e', '<endid> Stop refreshing at given article ID, useful for partial refreshing.', false, true );
		$this->addOption( 'n', '<numids> Stop refreshing after processing a given number of IDs, useful for partial refreshing.', false, true );
		$this->addOption( 'startidfile', '<startidfile> Read <startid> from a file instead of the arguments and write the next id to the file when finished. Useful for continual partial refreshing from cron.', false, true );
		$this->addOption( 'b', '<backend> Execute the operation for the storage backend of the given name (default is to use the current backend).', false, true );

		$this->addOption( 'f', 'Fully delete all content instead of just refreshing relevant entries. This will also rebuild the whole storage structure. May leave the wiki temporarily incomplete.', false );
		$this->addOption( 'v', 'Be verbose about the progress', false );
		$this->addOption( 'c', 'Will refresh only category pages (and other explicitly named namespaces)', false );
		$this->addOption( 'p', 'Will refresh only property pages (and other explicitly named namespaces)', false );
		$this->addOption( 't', 'Will refresh only type pages (and other explicitly named namespaces)', false );
		$this->addOption( 'runtime', 'Will display the runtime environment of the script', false );
		$this->addOption( 'page', '<pagelist> Will refresh only the pages of the given names, with | used as a separator. Example: --page "Page 1|Page 2" refreshes Page 1 and Page 2 Options -s, -e, -n, --startidfile, -c, -p, -t are ignored if --page is given.', false, true );
		$this->addOption( 'server', '<server> The protocol and server name to as base URLs, e.g. http://en.wikipedia.org. This is sometimes necessary because server name detection may fail in command line scripts.', false, true );
		$this->addOption( 'query', "<query> Will refresh only pages returned by a given query. Example: --query='[[Category:SomeCategory]]'", false, true );

		$this->addOption( 'quiet', 'Do not give any output', false );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		$start = microtime( true );
		$memoryBefore = memory_get_peak_usage( false );

		if ( !defined( 'SMW_VERSION' ) ) {
			$this->reportMessage( "You need to have SMW enabled in order to run the maintenance script!\n\n" );
			return false;
		}

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$store = StoreFactory::getStore( $this->hasOption( 'b' ) ? $this->getOption( 'b' ) : null );
		$store->setUpdateJobsEnabledState( false );

		$dataRebuilder = new DataRebuilder( $store );

		$dataRebuilder->setMessageReporter( $reporter );
		$dataRebuilder->setParameters( $this->mOptions );

		if ( $dataRebuilder->rebuild() ) {

			$this->doReportRuntimeEnvironment(
				$memoryBefore,
				memory_get_peak_usage( false ),
				microtime( true ) - $start
			);

			return true;
		}

		$this->reportMessage( $this->mDescription . "\n\n" . 'Use option --help for usage details.' . "\n"  );
		return false;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	private function doReportRuntimeEnvironment( $memoryBefore, $memoryAfter, $time ) {

		if ( !$this->hasOption( 'runtime' ) ) {
			return;
		}

		$time = round( $time, 2 ) . ' sec ' . ( $time > 60 ? '(' . round( $time / 60, 2 ) . ' min)' : '' );

		$this->reportMessage(
			"\n" . "Memory used: " . ( $memoryAfter - $memoryBefore ) .
			" (b: {$memoryBefore}, a: {$memoryAfter}) with a runtime of " . $time . "\n"
		);
	}

}

$maintClass = 'SMW\Maintenance\RebuildData';
require_once( RUN_MAINTENANCE_IF_MAIN );
