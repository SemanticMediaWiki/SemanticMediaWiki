<?php

namespace SMW\Maintenance;

use SMW\Exporter\ExporterFactory;
use SMW\Utils\CliMsgFormatter;
use SMW\Maintenance\MaintenanceCheck;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\CallbackMessageReporter;

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

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
 * --individuals      Export only pages that are no categories, properties, or types
 * --namespace <namespacelist>
 *                    Export only namespaces included in <namespacelist>
 *                    Example: --namespace "NS_MAIN|NS_CUSTOMNAMESPACE|NS_CATEGORY|SMW_NS_CONCEPT|SMW_NS_PROPERTY|SMW_NS_SCHEMA" with | being used as separator.
 *                    Uses constant namespace names.
 * --page <pagelist>  Export only pages included in the <pagelist> with | being used as a separator.
 *                    Example: --page "Page 1|Page 2", -e, -file, -d are ignored if --page is given.
 * -d <delay>         Slows down the export in order to stress the server less,
 *                    sleeping for <delay> milliseconds every now and then
 * -e <each>          After how many exported entities should the process take a nap?
 * --server=<server>  The protocol and server name to as base URLs, e.g.
 *                    https://en.wikipedia.org. This is sometimes necessary because
 *                    server name detection may fail in command line scripts.
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class dumpRDF extends \Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 2.0
	 */
	public function __construct() {
		parent::__construct();

		$this->addDescription( "RDF export of existing triples." );

		$this->addOption( 'd', '<delay> Wait for this many milliseconds after processing, useful for limiting server load.', false, true );
		$this->addOption( 'e', '<each> after how many exported entities should the process take a nap.', false, true );
		$this->addOption( 'file', '<file> output file.', false, true, 'o' );

		$this->addOption( 'categories', 'Export only categories', false );
		$this->addOption( 'concepts', 'Export only concepts', false );
		$this->addOption( 'classes', 'Export only classes', false );
		$this->addOption( 'properties', 'Export only properties', false );
		$this->addOption( 'individuals', 'Export only individuals', false );

        $this->addOption( 'namespace', 'Export only namespaced included in the <namespacelist> with | being used as a separator. ' .
            'Example: --namespace "NS_MAIN|NS_CUSTOMNAMESPACE"', false, true );


        $this->addOption( 'page', 'Export only pages included in the <pagelist> with | being used as a separator. ' .
								'Example: --page "Page 1|Page 2", -e, -file, -d are ignored if --page is given.', false, true );

		$this->addOption( 'server', '<server> The protocol and server name to as base URLs, e.g. http://en.wikipedia.org. ' .
								'This is sometimes necessary because server name detection may fail in command line scripts.', false, true );

		$this->addOption( 'quiet', 'Do not give any output', false, false, 'q' );
	}

	/**
	 * @since 3.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		if ( $this->messageReporter !== null ) {
			return $this->messageReporter->reportMessage( $message );
		}

		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 *
	 * @since 2.0
	 */
	public function execute() {
		if ( ( $maintenanceCheck = new MaintenanceCheck() )->canExecute() === false ) {
			exit ( $maintenanceCheck->getMessage() );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"This script is writting OWL/RDF information to the output or a selected file."
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Export task(s)' )
		);

		if ( $this->hasOption( 'file' ) ) {
			$this->reportMessage(
				$cliMsgFormatter->twoCols( 'File', $this->getOption( 'file' ) )
			);
		}

		$this->reportMessage( "\n" );

		return $this->runExport();
	}

	private function runExport() {
		$delay = 0;
		$pages = [];
		$restrictNamespaceTo = false;

		if ( $this->hasOption( 'd' ) ) {
			$delay = intval( $this->getOption( 'd' ) ) * 1000;
		}

		$delayeach = ( $delay === 0 ) ? 0 : 1;

		if ( $this->hasOption( 'e' ) ) {
			$delayeach = intval( $this->getOption( 'e' ) );
		}

		if ( $this->hasOption( 'categories' ) ) {
			$restrictNamespaceTo = NS_CATEGORY;
		} elseif ( $this->hasOption( 'concepts' ) ) {
			$restrictNamespaceTo = SMW_NS_CONCEPT;
		} elseif ( $this->hasOption( 'classes' ) ) {
			$restrictNamespaceTo = [ NS_CATEGORY, SMW_NS_CONCEPT ];
		} elseif ( $this->hasOption( 'properties' ) ) {
			$restrictNamespaceTo = SMW_NS_PROPERTY;
		} elseif ( $this->hasOption( 'individuals' ) ) {
			$restrictNamespaceTo = - 1;
		}

		if ( $this->hasOption( 'page' ) ) {
			$pages = explode( '|', $this->getOption( 'page' ) );
		}

        if ( $this->hasOption( 'namespace' ) ) {
            $restrictNamespaceTo = array_map( 'constant', explode( '|', $this->getOption( 'namespace' ) ) );
        }

        if ( $this->hasOption( 'server' ) ) {
			$GLOBALS['wgServer'] = $this->getOption( 'server' );
		}


		$exporterFactory = new ExporterFactory();

		$exportController = $exporterFactory->newExportController(
			$exporterFactory->newRDFXMLSerializer()
		);

		if ( $pages !== [] ) {
			$exportController->printPages(
				$pages
			);
		} elseif ( $this->hasOption( 'file' ) ) {
			$exportController->printAllToFile(
				$this->getOption( 'file' ),
				$restrictNamespaceTo,
				$delay,
				$delayeach
			);
		} else {
			$exportController->printAllToOutput(
				$restrictNamespaceTo,
				$delay,
				$delayeach
			);
		}

		return true;
	}

}

$maintClass = dumpRDF::class;
require_once ( RUN_MAINTENANCE_IF_MAIN );
