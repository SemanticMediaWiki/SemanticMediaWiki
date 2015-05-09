<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\ByJsonTestCaseProvider;
use SMW\Tests\JsonTestCaseFileHandler;

use SMW\Tests\Utils\UtilityFactory;

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
class ByJsonQueryTestCaseRunnerTest extends ByJsonTestCaseProvider {

	/**
	 * @var QueryTestCaseProcessor
	 */
	private $queryTestCaseProcessor;

	protected function setUp() {
		parent::setUp();

		$this->queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator(),
			UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator()
		);
	}

	/**
	 * @see ByJsonTestCaseProvider::getJsonTestCaseVersion
	 */
	protected function getJsonTestCaseVersion() {
		return '0.1';
	}

	/**
	 * @see ByJsonTestCaseProvider::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ . '/Fixtures';
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
			'wgContLang',
			'wgLang',
			'smwgQMaxSize',
			'smwStrictComparators',
			'smwgNamespacesWithSemanticLinks',
			'smwgQSubpropertyDepth',
			'smwgQSubcategoryDepth'
		);

		foreach ( $permittedSettings as $key ) {
			$this->changeGlobalSettingTo(
				$key,
				$jsonTestCaseFileHandler->getSettingsFor( $key )
			);
		}

		// Set query parser late to ensure that expected settings are adjusted
		// (language etc.) because the __construct relies on the context language
		$this->queryTestCaseProcessor->setQueryParser(
			ApplicationFactory::getInstance()->newQueryParser()
		);

		$this->createPagesFor(
			$jsonTestCaseFileHandler->getListOfProperties(),
			SMW_NS_PROPERTY
		);

		$this->createPagesFor(
			$jsonTestCaseFileHandler->getListOfSubjects(),
			NS_MAIN
		);

		$this->queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesFor( 'query-testcases' ) as $queryCase ) {
			$this->queryTestCaseProcessor->processQueryCase( new QueryTestCaseInterpreter( $queryCase ) );
		}

		foreach ( $jsonTestCaseFileHandler->findTestCasesFor( 'concept-testcases' ) as $conceptCase ) {
			$this->queryTestCaseProcessor->processConceptCase( new QueryTestCaseInterpreter( $conceptCase ) );
		}

		foreach ( $jsonTestCaseFileHandler->findTestCasesFor( 'format-testcases' ) as $formatCase ) {
			$this->queryTestCaseProcessor->processFormatCase( new QueryTestCaseInterpreter( $formatCase ) );
		}
	}

}
