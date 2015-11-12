<?php

namespace SMW\Tests\Integration\ByJsonScript;

use SMW\Tests\ByJsonTestCaseProvider;
use SMW\Tests\JsonTestCaseFileHandler;
use SMW\Tests\Utils\UtilityFactory;
use SMW\EventHandler;
use SMW\ApplicationFactory;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ByJsonScriptFixtureTestCaseRunnerTest extends ByJsonTestCaseProvider {

	/**
	 * @var QueryTestCaseProcessor
	 */
	private $queryTestCaseProcessor;

	/**
	 * @var RdfTestCaseProcessor
	 */
	private $rdfTestCaseProcessor;

	/**
	 * @var ParserTestCaseProcessor
	 */
	private $parserTestCaseProcessor;

	/**
	 * @var EventDispatcher
	 */
	private $eventDispatcher;

	protected function setUp() {
		parent::setUp();

		$validatorFactory = UtilityFactory::getInstance()->newValidatorFactory();

		$stringValidator = $validatorFactory->newStringValidator();
		$semanticDataValidator = $validatorFactory->newSemanticDataValidator();
		$queryResultValidator = $validatorFactory->newQueryResultValidator();

		$this->queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$queryResultValidator,
			$stringValidator
		);

		$this->rdfTestCaseProcessor = new RdfTestCaseProcessor(
			$this->getStore(),
			$stringValidator
		);

		$this->parserTestCaseProcessor = new ParserTestCaseProcessor(
			$this->getStore(),
			$semanticDataValidator,
			$stringValidator
		);

		$this->eventDispatcher = EventHandler::getInstance()->getEventDispatcher();
	}

	/**
	 * @see ByJsonTestCaseProvider::canExecuteTestCasesFor
	 */
	protected function canExecuteTestCasesFor( $file ) {

		$selectedFiles = array();

		if ( $selectedFiles === array() ) {
			return true;
		}

		foreach ( $selectedFiles as $f ) {
			if ( strpos( $file, $f ) !== false ) {
				return true;
			}
		}

		return false;
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
			'smwgNamespacesWithSemanticLinks',
			'smwgPageSpecialProperties',
			'wgLanguageCode',
			'wgContLang',
			'wgLang',
			'wgCapitalLinks',
			'smwgNamespace',
			'smwgExportBCNonCanonicalFormUse',
			'smwgExportBCAuxiliaryUse',
			'smwgQMaxSize',
			'smwStrictComparators',
			'smwgQSubpropertyDepth',
			'smwgQSubcategoryDepth',
			'smwgQConceptCaching',
			'smwgEnabledInTextAnnotationParserStrictMode'
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

		$this->tryToProcessParserTestCase( $jsonTestCaseFileHandler );
		$this->tryToProcessRDFTestCase( $jsonTestCaseFileHandler );
		$this->tryToProcessQueryTestCase( $jsonTestCaseFileHandler );
	}

	private function tryToProcessParserTestCase( $jsonTestCaseFileHandler ) {

		$this->parserTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesFor( 'parser-testcases' ) as $case ) {
			$this->parserTestCaseProcessor->process( $case );
		}
	}

	private function tryToProcessRDFTestCase( $jsonTestCaseFileHandler ) {

		// For some reason there are some random failures where
		// the instance hasn't reset the cache in time to fetch the
		// property definition which only happens for the SPARQLStore

		// This should not be necessary because the resetcache event
		// is triggered`
		$this->eventDispatcher->dispatch( 'exporter.reset' );
		$this->eventDispatcher->dispatch( 'query.comparator.reset' );

		$this->rdfTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesFor( 'rdf-testcases' ) as $case ) {
			$this->rdfTestCaseProcessor->process( $case );
		}
	}

	private function tryToProcessQueryTestCase( $jsonTestCaseFileHandler ) {

		// Set query parser late to ensure that expected settings are adjusted
		// (language etc.) because the __construct relies on the context language
		$this->queryTestCaseProcessor->setQueryParser(
			ApplicationFactory::getInstance()->newQueryParser()
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
