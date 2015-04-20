<?php

namespace SMW\Tests\Integration\Rdf;

use SMW\Tests\ByJsonTestCaseProvider;
use SMW\Tests\JsonTestCaseFileHandler;

use SMW\Tests\Utils\UtilityFactory;
use SMWExportController as ExportController;
use SMWRDFXMLSerializer as RDFXMLSerializer;
use SMW\ApplicationFactory;
use SMW\EventHandler;
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
	 * @see ByJsonTestCaseProvider::getJsonTestCaseVersion
	 */
	protected function getJsonTestCaseVersion() {
		return '0.2';
	}

	/**
	 * @see ByJsonTestCaseProvider::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__;
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
			'smwgNamespacesWithSemanticLinks',
			'wgLanguageCode',
			'wgContLang'
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

		// For some reason there are some random failures where
		// the instance hasn't reset the cache in time to fetch the
		// property definition which only happens for the SPARQLStore

		// This should not be necessary because the resetcache event
		// is triggered
		EventHandler::getInstance()->getEventDispatcher()->dispatch( 'exporter.reset' );

		foreach ( $jsonTestCaseFileHandler->findTestCasesFor( 'rdf-testcases' ) as $case ) {
			$this->assertRdfOutputForCase( $case, $jsonTestCaseFileHandler->getDebugMode() );
		}
	}

	private function assertRdfOutputForCase( $case, $debug ) {

		$exportController = new ExportController( new RDFXMLSerializer() );
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

		if ( $debug ) {
			print_r( $output );
		}

		$this->stringValidator->assertThatStringContains(
			$case['expected-output']['to-contain'],
			$output,
			$case['about']
		);
	}

}
