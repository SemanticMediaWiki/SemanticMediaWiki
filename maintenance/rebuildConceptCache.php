<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\Setup;
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
 * Manage concept caches
 *
 * This script is used to manage concept caches for Semantic MediaWiki. Concepts
 * are semantic queries stored on Concept: pages. The elements of concepts can be
 * computed online, or they can come from a pre-computed cache. The wiki may even
 * be configured to display certain concepts only if they are available cached.
 *
 * This script can create, delete and update these caches, or merely show their
 * status.
 *
 * Usage: php rebuildConceptCache.php <action> [<select concepts>] [<options>]
 *
 * Actions:
 * --help      Show this message.
 * --status    Show the cache status of the selected concepts.
 * --create    Rebuild caches for the selected concepts.
 * --delete    Remove all caches for the selected concepts.
 *
 * If no further options are given, all concepts in the wiki are processed.
 *
 * Select concepts:
 * --concept       'Concept name' Process only this one concept.
 * --hard          Process only concepts that are not allowed to be computed
 *                 online according to the current wiki settings.
 * --update        Process only concepts that already have some cache, i.e. do
 *                 not create any new caches. For the opposite (only concepts
 *                 without caches), use --old with a very high number.
 * --old <min>     Process only concepts with caches older than <min> minutes
 *                 or with no caches at all.
 * -s <startid>    Process only concepts with page id of at least <startid>
 * -e <endid>      Process only concepts with page id of at most <endid>
 *
 * Selection options can be combined to process only concepts that meet all the
 * requirements at once. If --concept is given, then -s and -e are ignored.
 *
 * Options:
 * --quiet         Do not give any output.
 * --verbose       Give additional output. No effect if --quiet is given.
 *
 * Use option --help for usage details.
 *
 * Note: if SMW is not installed in its standard path under ./extensions
 *       then the MW_INSTALL_PATH environment variable must be set.
 *       See README in the maintenance directory.
 *
 * @ingroup Maintenance
 *
 * @licence GNU GPL v2+
 * @since 1.9.2
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class rebuildConceptCache extends \Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Maintenance script to manage concept caches in Semantic MediaWiki." );

		// Actions
		$this->addOption( 'status', 'Show the cache status of the selected concepts' );
		$this->addOption( 'create', 'Rebuild caches for the selected concepts.' );
		$this->addOption( 'delete', 'Remove all caches for the selected concepts.' );

		// Options
		$this->addOption( 'concept', '"Concept name" Process only this one concept.', false, true );
		$this->addOption( 'hard', 'Process only concepts that are not allowed to be computed online according to the current wiki settings.' );

		$this->addOption( 'update', 'Process only concepts that already have some cache, i.e. do not create any new caches. ' .
								'For the opposite (only concepts without caches), use --old with a very high number.' );

		$this->addOption( 'old', '<min> Process only concepts with caches older than <min> minutes or with no caches at all.', false, true );
		$this->addOption( 's', '<startid> Process only concepts with page id of at least <startid>', false, true );
		$this->addOption( 'e', '<endid> Process only concepts with page id of at most <endid>', false, true );

		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'debug', 'Sets global variables to support debug ouput while running the script', false );
		$this->addOption( 'quiet', 'Do not give any output', false );
		$this->addOption( 'verbose', 'Give additional output. No effect if --quiet is given.', false );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( $this->canExecute() !== true ) {
			exit;
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"This script is used to manage concept caches for Semantic MediaWiki. Concepts",
			"are semantic queries stored on Concept: pages. The elements of concepts can be",
			"computed online, or they can come from a pre-computed cache. The wiki may even",
			"be configured to display certain concepts only if they are available cached.",
			"\n\n",
			"This script can create, delete and update these caches, or merely show their",
			"status. "
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		if ( $this->hasOption( 'debug' ) ) {
			$maintenanceHelper->setGlobalToValue( 'wgShowExceptionDetails', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowSQLErrors', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowDBErrorBacktrace', true );
		}

		$conceptCacheRebuilder = $maintenanceFactory->newConceptCacheRebuilder(
			$applicationFactory->getStore(),
			[ $this, 'reportMessage' ]
		);

		$conceptCacheRebuilder->setParameters( $this->mOptions );

		$result = $this->checkForRebuildState(
			$conceptCacheRebuilder->rebuild()
		);

		if ( $result && $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage( $cliMsgFormatter->section( 'Runtime report' ) );

			$this->reportMessage(
				"\n" . $maintenanceHelper->getFormattedRuntimeValues()
			);
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildConceptCacheLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used' => $runtimeValues['memory-used'],
				'Time used' => $runtimeValues['humanreadable-time']
			];

			$maintenanceLogger->logFromArray( $log );
		}

		$maintenanceHelper->reset();

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

	private function checkForRebuildState( $rebuildResult ) {

		if ( !$rebuildResult ) {
			$this->reportMessage( "\n" . 'Use option --help for usage details.' . "\n"  );
			return false;
		}

		return true;
	}

}

$maintClass = rebuildConceptCache::class;
require_once ( RUN_MAINTENANCE_IF_MAIN );
