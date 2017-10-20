<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\ApplicationFactory;
use SMWDataItem as DataItem;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RebuildFulltextSearchTable extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Rebuild the fulltext search index (only works with SQLStore)';
		$this->addOption( 'report-runtime', 'Report execution time and memory usage', false );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
		$this->addOption( 'optimize', 'Run possible table optimization (support depends on the SQL back-end) ', false );
		$this->addOption( 'v', 'Show additional (verbose) information about the progress', false );
		$this->addOption( 'quick', 'Suppress abort operation', false );

		parent::__construct();
	}


	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 2.5
	 */
	protected function addDefaultParams() {

		parent::addDefaultParams();
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !defined( 'SMW_VERSION' ) || !$GLOBALS['smwgSemanticsEnabled'] ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		// Only the SQLStore is supported
		$searchTableRebuilder = $fulltextSearchTableFactory->newSearchTableRebuilder(
			$applicationFactory->getStore( '\SMW\SQLStore\SQLStore' )
		);

		$textSanitizer = $fulltextSearchTableFactory->newTextSanitizer();

		$searchTableRebuilder->reportVerbose(
			$this->hasOption( 'v' )
		);

		$searchTableRebuilder->requestOptimization(
			$this->hasOption( 'optimize' )
		);

		$this->reportMessage(
			"\nThe script rebuilds the search index from property tables that\n" .
			"support a fulltext search. Any change of the index rules (altered\n".
			"stopwords, new stemmer etc.) and/or a newly added or altered table\n".
			"requires to run this script again to ensure that the index complies\n".
			"with the rules set forth by the SQL back-end or Sanitizer.\n"
		);

		$this->reportConfiguration(
			$searchTableRebuilder,
			$textSanitizer
		);

		if ( !$this->hasOption( 'quick' ) ) {
			$this->reportMessage( "\n" . 'Abort the rebuild with control-c in the next five seconds ...  ' );
			wfCountDown( 5 );
		}

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		// Need to instantiate an extra object here since we cannot make this class itself
		// into a MessageReporter since the maintenance script does not load the interface in time.
		$reporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$searchTableRebuilder->setMessageReporter( $reporter );
		$result = $searchTableRebuilder->rebuild();

		if ( $result && $this->hasOption( 'report-runtime' ) ) {
			$this->reportMessage(
				"\n" . $maintenanceHelper->getFormattedRuntimeValues() . "\n"
			);
		}

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'RebuildFulltextSearchTableLogger' );
			$maintenanceLogger->log( $maintenanceHelper->getFormattedRuntimeValues() );
		}

		$maintenanceHelper->reset();
		return $result;
	}

	private function reportConfiguration( $searchTableRebuilder, $textSanitizer ) {

		$this->reportMessage(
			"\n## Configuration\n\n"
		);

		foreach ( $textSanitizer->getVersions() as $key => $value ) {
			$this->reportMessage( "\r". sprintf( "%-35s%s", "- {$key}", $value )  . "\n" );
		}

		$searchTable = $searchTableRebuilder->getSearchTable();
		$indexableDataTypes = array();

		$dataTypes = array(
			DataItem::TYPE_BLOB => 'BLOB',
			DataItem::TYPE_URI  => 'URI',
			DataItem::TYPE_WIKIPAGE => 'WIKIPAGE'
		);

		foreach ( $dataTypes as $key => $value ) {
			if ( $searchTable->isValidByType( $key ) ) {
				$indexableDataTypes[] = $value;
			}
		}

		$this->reportMessage(
			"\r". sprintf( "%-35s%s", "- DataTypes (Indexable)", implode( ', ', $indexableDataTypes ) )  . "\n"
		);

		$this->reportMessage(
			"\nThe following properties are exempted from the indexing process.\n"
		);

		$exemptionList = '';

		foreach ( $searchTable->getPropertyExemptionList() as $prop ) {
			$exemptionList .= ( $exemptionList === '' ? '' : ', ' ) . $prop;

			if ( strlen( $exemptionList ) > 60 ) {
				$this->reportMessage( "\n- " . $exemptionList );
				$exemptionList = '';
			}
		}

		$this->reportMessage( "\n- " . $exemptionList . "\n" );
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

}

$maintClass = 'SMW\Maintenance\RebuildFulltextSearchTable';
require_once ( RUN_MAINTENANCE_IF_MAIN );
