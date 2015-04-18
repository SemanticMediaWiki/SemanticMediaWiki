<?php

namespace SMW\Maintenance;

use SMW\Maintenance\ConceptCacheRebuilder;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\ApplicationFactory;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

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
class RebuildConceptCache extends \Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( "\n" .
			"This script is used to manage concept caches for Semantic MediaWiki. Concepts \n" .
			"are semantic queries stored on Concept: pages. The elements of concepts can be \n" .
			"computed online, or they can come from a pre-computed cache. The wiki may even \n" .
			"be configured to display certain concepts only if they are available cached. \n" .
			"\n" . "This script can create, delete and update these caches, or merely show their \n".
			"status. "
		);

		$this->addDefaultParams();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 */
	protected function addDefaultParams() {

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

		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'debug', 'Sets global variables to support debug ouput while running the script', false );
		$this->addOption( 'quiet', 'Do not give any output', false );
		$this->addOption( 'verbose', 'Give additional output. No effect if --quiet is given.', false );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !defined( 'SMW_VERSION' ) ) {
			$this->reportMessage( "You need to have SMW enabled in order to run the maintenance script!\n\n" );
			return false;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		if ( $this->hasOption( 'debug' ) ) {
			$maintenanceHelper->setGlobalToValue( 'wgShowExceptionDetails', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowSQLErrors', true );
			$maintenanceHelper->setGlobalToValue( 'wgShowDBErrorBacktrace', true );
		}

		$reporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$conceptCacheRebuilder = $maintenanceFactory->newConceptCacheRebuilder( $applicationFactory->getStore() );
		$conceptCacheRebuilder->setMessageReporter( $reporter );
		$conceptCacheRebuilder->setParameters( $this->mOptions );

		$result = $this->checkForRebuildState( $conceptCacheRebuilder->rebuild() );

		if ( $result && $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage( "\n" . $maintenanceHelper->transformRuntimeValuesForOutput() . "\n" );
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

	private function checkForRebuildState( $rebuildResult ) {

		if ( !$rebuildResult ) {
			$this->reportMessage( $this->mDescription . "\n\n" . 'Use option --help for usage details.' . "\n"  );
			return false;
		}

		return true;
	}

}

$maintClass = 'SMW\Maintenance\RebuildConceptCache';
require_once ( RUN_MAINTENANCE_IF_MAIN );
