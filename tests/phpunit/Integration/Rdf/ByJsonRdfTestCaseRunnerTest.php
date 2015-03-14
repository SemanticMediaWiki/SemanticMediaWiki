<?php

namespace SMW\Tests\Integration\Rdf;

use SMW\Tests\ByJsonTestCaseProvider;
use SMW\Tests\JsonTestCaseFileHandler;

use SMW\Tests\Utils\UtilityFactory;
use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;
use SMW\ApplicationFactory;
use Title;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ByJsonRdfTestCaseRunnerTest extends ByJsonTestCaseProvider {

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
	}

	/**
	 * Version to match supported Json format
	 *
	 * @see ByJsonTestCaseProvider::getJsonTestCaseVersion
	 */
	protected function getJsonTestCaseVersion() {
		return '0.1';
	}

	/**
	 * @see ByJsonTestCaseProvider::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ ;
	}

	/**
	 * @see ByJsonTestCaseProvider::runTestCaseFile
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->checkEnvironmentToSkipCurrentTest( $jsonTestCaseFileHandler );

		$permittedSettings = array(
			'smwgNamespace',
			'smwgNamespacesWithSemanticLinks'
		);

		foreach ( $permittedSettings as $key ) {
			$this->changeGlobalSettingTo(
				$key,
				$jsonTestCaseFileHandler->getSettingsFor( $key )
			);
		}

		$this->createPagesFor(
			$jsonTestCaseFileHandler->getListOfProperties(),
			SMW_NS_PROPERTY
		);

		$this->createPagesFor(
			$jsonTestCaseFileHandler->getListOfSubjects(),
			NS_MAIN
		);

		\SMWExporter::getInstance()->clear();

		foreach ( $jsonTestCaseFileHandler->findRdfTestCases() as $case ) {
			$this->assertRdfOutputForCase( $case, $jsonTestCaseFileHandler->getDebugMode() );
		}
	}

	private function assertRdfOutputForCase( $case, $debug ) {

		$exportController = new ExportController( new RDFXMLSerializer() );
		$exportController->enableBacklinks( $case['parameters']['backlinks'] );

		ob_start();

		$exportController->printPages(
			$case['exportcontroller-print-pages'],
			(int)$case['parameters']['recursion'],
			$case['parameters']['revisiondate']
		);

		$output = ob_get_clean();

		if ( $debug ) {
			print_r( $output );
		}

		$this->stringValidator->assertThatStringContains(
			$case['output']['as-string'],
			$output,
			$case['about']
		);
	}

}
