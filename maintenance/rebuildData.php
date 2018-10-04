<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\StoreFactory;
use SMW\Store;
use SMW\Setup;
use SMW\Options;

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

		parent::addDefaultParams();

		$this->addOption( 'd', '<delay> Wait for this many milliseconds after processing an article, useful for limiting server load.', false, true );
		$this->addOption( 's', '<startid> Start refreshing at given article ID, useful for partial refreshing.', false, true );
		$this->addOption( 'e', '<endid> Stop refreshing at given article ID, useful for partial refreshing.', false, true );
		$this->addOption( 'n', '<numids> Stop refreshing after processing a given number of IDs, useful for partial refreshing.', false, true );

		$this->addOption( 'startidfile', '<startidfile> Read <startid> from a file instead of the arguments and write the next id to the file when finished. ' .
								'Useful for continual partial refreshing from cron.', false, true );

		$this->addOption( 'b', '<backend> Execute the operation for the storage backend of the given name (default is to use the current backend).', false, true );

		$this->addOption( 'f', 'Fully delete all content instead of just refreshing relevant entries. This will also rebuild the whole storage structure. ' .
								'May leave the wiki temporarily incomplete.', false );

		$this->addOption( 'v', 'Be verbose about the progress', false );
		$this->addOption( 'p', 'Only refresh property pages (and other explicitly named namespaces)', false );
		$this->addOption( 'categories', 'Only refresh category pages (and other explicitly named namespaces)', false, false, 'c' );
		$this->addOption( 'redirects', 'Only refresh redirect pages', false );
		$this->addOption( 'dispose-outdated', 'Only Remove outdated marked entities (including pending references).', false );

		$this->addOption( 'skip-properties', 'Skip the default properties rebuild (only recommended when successive build steps are used)', false );
		$this->addOption( 'shallow-update', 'Skip processing of entities that compare to the last known revision date', false );
		$this->addOption( 'property-statistics', 'Execute `rebuildPropertyStatistics` after the `rebuildData` run has finished.', false );

		$this->addOption( 'force-update', 'Force an update even when an associated revision is known', false );
		$this->addOption( 'revision-mode', 'Skip entities where its associated revision matches the latests referenced revision of an associated page', false );

		$this->addOption( 'ignore-exceptions', 'Ignore exceptions and log exception to a file', false );
		$this->addOption( 'exception-log', 'Exception log file location (e.g. /tmp/logs/)', false, true );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );

		$this->addOption( 'page', '<pagelist> Will refresh only the pages of the given names, with | used as a separator. ' .
								'Example: --page "Page 1|Page 2" refreshes Page 1 and Page 2 Options -s, -e, -n, ' .
								'--startidfile, -c, -p, -t are ignored if --page is given.', false, true );

		$this->addOption( 'server', '<server> The protocol and server name to as base URLs, e.g. http://en.wikipedia.org. ' .
								'This is sometimes necessary because server name detection may fail in command line scripts.', false, true );

		$this->addOption( 'query', "<query> Will refresh only pages returned by a given query. Example: --query='[[Category:SomeCategory]]'", false, true );

		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'report-poolcache', 'Report internal poolcache memory usage', false );
		$this->addOption( 'no-cache', 'Sets the `wgMainCacheType` to none while running the script', false );
		$this->addOption( 'debug', 'Sets global variables to support debug ouput while running the script', false );
		$this->addOption( 'quiet', 'Do not give any output', false );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !Setup::isEnabled() ) {
			$this->reportMessage( "\nYou need to have SMW enabled in order to run the maintenance script!\n" );
			exit;
		}

		if ( !Setup::isValid( true ) ) {
			$this->reportMessage( "\nYou need to run `update.php` or `setupStore.php` first before continuing\nwith any maintenance tasks!\n" );
			exit;
		}

		$maintenanceFactory = ApplicationFactory::getInstance()->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		if ( $this->hasOption( 'no-cache' ) ) {
			$maintenanceHelper->setGlobalToValue( 'wgMainCacheType', CACHE_NONE );
			$maintenanceHelper->setGlobalToValue( 'smwgEntityLookupCacheType', CACHE_NONE );
			$maintenanceHelper->setGlobalToValue( 'smwgQueryResultCacheType', CACHE_NONE );
		}

		if ( $this->hasOption( 'debug' ) ) {
			$maintenanceHelper->setGlobalToValue( 'wgShowExceptionDetails', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowSQLErrors', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowDBErrorBacktrace', true );
		} else {
			$maintenanceHelper->setGlobalToValue( 'wgDebugLogFile', '' );
			$maintenanceHelper->setGlobalToValue( 'wgDebugLogGroups', [] );
		}

		$store = StoreFactory::getStore( $this->hasOption( 'b' ) ? $this->getOption( 'b' ) : null );
		$store->setOption( Store::OPT_CREATE_UPDATE_JOB, false );

		$dataRebuilder = $maintenanceFactory->newDataRebuilder(
			$store,
			[ $this, 'reportMessage' ]
		);

		$dataRebuilder->setOptions(
			new Options( $this->mOptions )
		);

		$result = $this->checkForRebuildState(
			$dataRebuilder->rebuild()
		);

		if ( $result && $this->hasOption( 'property-statistics' ) ) {
			$rebuildPropertyStatistics = $maintenanceFactory->newRebuildPropertyStatistics();
			$rebuildPropertyStatistics->execute();
		}

		if ( $result && $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage( "\n" . "Runtime report ..." . "\n" );
			$this->reportMessage( $maintenanceHelper->getFormattedRuntimeValues( '   ...' ) . "\n" );
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildDataLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used: ' . $runtimeValues['memory-used'],
				'Time used: ' . $runtimeValues['humanreadable-time'],
				'Rebuild count: ' . $dataRebuilder->getRebuildCount(),
				'Exception count: ' . $dataRebuilder->getExceptionCount()
			];

			$maintenanceLogger->log( implode( ', ', $log ) );
		}

		$maintenanceHelper->reset();

		if ( $this->hasOption( 'report-poolcache' ) ) {
			$this->reportMessage( "\n" . ApplicationFactory::getInstance()->getInMemoryPoolCache()->getStats( \SMW\Utils\StatsFormatter::FORMAT_JSON ) . "\n" );
		}

		return $result;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	private function checkForRebuildState( $rebuildResult ) {

		if ( !$rebuildResult ) {
			$this->reportMessage( $this->mDescription . "\n\n" . 'Use option --help for usage details.' . "\n"  );
			return false;
		}

		return true;
	}

}

$maintClass = 'SMW\Maintenance\RebuildData';
require_once ( RUN_MAINTENANCE_IF_MAIN );
