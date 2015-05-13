<?php

namespace SMW\Maintenance;

use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Usage:
 * php dumpRDF.php [options...]
 *
 * --file (-o) <file> Export everything to given output file, stdout is used if omitted;
 *                    file output is generally better and strongly recommended for large wikis
 * --categories       Export only categories
 * --concepts         Export only concepts
 * --classes          Export only concepts and categories
 * --properties       Export only properties
 * --types            Export only types
 * --individuals      Export only pages that are no categories, properties, or types
 * --page <pagelist>  Export only pages included in the <pagelist> with | being used as a separator.
 *                    Example: --page "Page 1|Page 2", -e, -file, -d are ignored if --page is given.
 * -d <delay>         Slows down the export in order to stress the server less,
 *                    sleeping for <delay> milliseconds every now and then
 * -e <each>          After how many exported entities should the process take a nap?
 * --server=<server>  The protocol and server name to as base URLs, e.g.
 *                    https://en.wikipedia.org. This is sometimes necessary because
 *                    server name detection may fail in command line scripts.
 *
 * @ingroup SMWMaintenance
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class DumpRdf extends \Maintenance {

	private $delay = 0;
	private $delayeach = 0;

	/**
	 * @var boolean|array
	 */
	private $restrictNamespaceTo = false;

	/**
	 * @var array
	 */
	private $pages = array();

	/**
	 * @since 2.0
	 */
	public function __construct() {
		parent::__construct();

		$this->addDescription( "\n" ."Complete RDF export of existing triples. \n" );
		$this->addDefaultParams();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 2.0
	 */
	protected function addDefaultParams() {

		$this->addOption( 'd', '<delay> Wait for this many milliseconds after processing, useful for limiting server load.', false, true );
		$this->addOption( 'e', '<each> after how many exported entities should the process take a nap.', false, true );
		$this->addOption( 'file', '<file> output file.', false, true, 'o' );

		$this->addOption( 'categories', 'Export only categories', false );
		$this->addOption( 'concepts', 'Export only concepts', false );
		$this->addOption( 'classes', 'Export only classes', false );
		$this->addOption( 'properties', 'Export only properties', false );
		$this->addOption( 'types', 'Export only types', false );
		$this->addOption( 'individuals', 'Export only individuals', false );

		$this->addOption( 'page', 'Export only pages included in the <pagelist> with | being used as a separator. ' .
								'Example: --page "Page 1|Page 2", -e, -file, -d are ignored if --page is given.', false, true );

		$this->addOption( 'server', '<server> The protocol and server name to as base URLs, e.g. http://en.wikipedia.org. ' .
								'This is sometimes necessary because server name detection may fail in command line scripts.', false, true );

		$this->addOption( 'quiet', 'Do not give any output', false, false, 'q' );
	}

	/**
	 * @see Maintenance::execute
	 *
	 * @since 2.0
	 */
	public function execute() {

		if ( !defined( 'SMW_VERSION' ) ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$this->reportMessage( "\nWriting OWL/RDF dump to " . $this->getOption( 'file' ) . " ...\n" );
		$this->setParameters()->exportRdfToOutputChannel();

		return true;
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 2.0
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	private function setParameters() {

		if ( $this->hasOption( 'd' ) ) {
			$this->delay = intval( $this->getOption( 'd' ) ) * 1000;
		}

		$this->delayeach = ( $this->delay === 0 ) ? 0 : 1;

		if ( $this->hasOption( 'e' ) ) {
			$this->delayeach = intval( $this->getOption( 'e' )  );
		}

		if ( $this->hasOption( 'categories' ) ) {
			$this->restrictNamespaceTo = NS_CATEGORY;
		} elseif ( $this->hasOption( 'concepts' ) ) {
			$this->restrictNamespaceTo = SMW_NS_CONCEPT;
		} elseif ( $this->hasOption( 'classes' ) ) {
			$this->restrictNamespaceTo = array( NS_CATEGORY, SMW_NS_CONCEPT );
		} elseif ( $this->hasOption( 'properties' ) ) {
			$this->restrictNamespaceTo = SMW_NS_PROPERTY;
		} elseif ( $this->hasOption( 'types' ) ) {
			$this->restrictNamespaceTo = SMW_NS_TYPE;
		} elseif ( $this->hasOption( 'individuals' ) ) {
			$this->restrictNamespaceTo = - 1;
		}

		if ( $this->hasOption( 'page' ) ) {
			$this->pages = explode( '|', $this->getOption( 'page' ) );
		}

		if ( $this->hasOption( 'server' ) ) {
			$GLOBALS['wgServer'] = $this->getOption( 'server' );
		}

		return $this;
	}

	private function exportRdfToOutputChannel() {

		$exportController = new ExportController( new RDFXMLSerializer() );

		if ( $this->pages !== array() ) {
			return $exportController->printPages(
				$this->pages
			);
		}

		if ( $this->hasOption( 'file' ) ) {
			return $exportController->printAllToFile(
				$this->getOption( 'file' ),
				$this->restrictNamespaceTo,
				$this->delay,
				$this->delayeach
			);
		}

		$exportController->printAllToOutput(
			$this->restrictNamespaceTo,
			$this->delay,
			$this->delayeach
		);
	}

}

$maintClass = 'SMW\Maintenance\DumpRdf';
require_once ( RUN_MAINTENANCE_IF_MAIN );
