<?php

namespace SMW\Tests\Utils\JSONScript;

use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class RdfTestCaseProcessor extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	/**
	 * @var RunnerFactory
	 */
	private $runnerFactory;

	/**
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @param Store
	 * @param StringValidator
	 */
	public function __construct( $store, $stringValidator, $runnerFactory ) {
		$this->store = $store;
		$this->stringValidator = $stringValidator;
		$this->runnerFactory = $runnerFactory;
	}

	/**
	 * @since  2.2
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	public function process( array $case ) {

		// Allows for data to be re-read from the DB instead of being fetched
		// from the store-id-cache
		if ( isset( $case['store']['clear-cache'] ) && $case['store']['clear-cache'] ) {
			$this->store->clear();
		}

		if ( isset( $case['dumpRDF'] ) ) {
			$this->assertDumpRdfOutputForCase( $case );
		}

		if ( isset( $case['exportcontroller'] ) ) {
			$this->assertExportControllerOutputForCase( $case );
		}
	}

	private function assertDumpRdfOutputForCase( $case ) {

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( 'SMW\Maintenance\DumpRdf' );
		$maintenanceRunner->setQuiet();

		$maintenanceRunner->setOptions( $case['dumpRDF']['parameters'] );
		$maintenanceRunner->run();

		$this->assertOutputForCase(
			$case,
			$maintenanceRunner->getOutput()
		);
	}

	private function assertExportControllerOutputForCase( $case ) {

		if ( isset( $case['exportcontroller']['syntax'] ) && $case['exportcontroller']['syntax'] === 'turtle' ) {
			$serializer = new TurtleSerializer();
		} else {
			$serializer = new RDFXMLSerializer();
		}

		$exportController = new ExportController( $serializer );
		$exportController->enableBacklinks( $case['exportcontroller']['parameters']['backlinks'] );

		ob_start();

		if ( isset( $case['exportcontroller']['print-pages'] ) ) {
			$exportController->printPages(
				$case['exportcontroller']['print-pages'],
				(int)$case['exportcontroller']['parameters']['recursion'],
				$case['exportcontroller']['parameters']['revisiondate']
			);
		}

		if ( isset( $case['exportcontroller']['wiki-info'] ) ) {
			$exportController->printWikiInfo();
		}

		$output = ob_get_clean();

		$this->assertOutputForCase( $case, $output );
	}

	private function assertOutputForCase( $case, $output ) {

		if ( $this->debug ) {
			print_r( $output );
		}

		if ( isset( $case['assert-output']['to-contain'] ) ) {
			$this->stringValidator->assertThatStringContains(
				$case['assert-output']['to-contain'],
				$output,
				$case['about']
			);
		}

		if ( isset( $case['assert-output']['not-contain'] ) ) {
			$this->stringValidator->assertThatStringNotContains(
				$case['assert-output']['not-contain'],
				$output,
				$case['about']
			);
		}
	}

}
