<?php

namespace SMW\Tests\Integration\ByJsonScript;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\EventHandler;
use SMW\PropertySpecificationLookup;
use SMW\Tests\ByJsonTestCaseProvider;
use SMW\Tests\JsonTestCaseFileHandler;
use SMW\Tests\Utils\UtilityFactory;

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
	 * @var SpecialPageTestCaseProcessor
	 */
	private $specialPageTestCaseProcessor;

	/**
	 * @var EventDispatcher
	 */
	private $eventDispatcher;

	/**
	 * @see ByJsonTestCaseProvider::$deletePagesOnTearDown
	 */
	protected $deletePagesOnTearDown = true;

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

		$this->specialPageTestCaseProcessor = new SpecialPageTestCaseProcessor(
			$this->getStore(),
			$stringValidator
		);

		$this->eventDispatcher = EventHandler::getInstance()->getEventDispatcher();

		// This ensures that if content is created in the NS_MEDIAWIKI namespace
		// and an object relies on the MediaWikiNsContentReader then it uses the DB
		ApplicationFactory::getInstance()->getMediaWikiNsContentReader()->skipMessageCache();
		DataValueFactory::getInstance()->clear();

		// Reset the Title/TitleParser otherwise a singleton instance holds an outdated
		// content language reference
		$this->testEnvironment->resetMediaWikiService( '_MediaWikiTitleCodec' );
		$this->testEnvironment->resetMediaWikiService( 'TitleParser' );

		$this->testEnvironment->resetPoolCacheFor( PropertySpecificationLookup::POOLCACHE_ID );

		// Make sure LocalSettings don't interfere with the default settings
		$GLOBALS['smwgDVFeatures'] = $GLOBALS['smwgDVFeatures'] & ~SMW_DV_NUMV_USPACE;
	}

	/**
	 * @see ByJsonTestCaseProvider::canExecuteTestCasesFor
	 */
	protected function canExecuteTestCasesFor( $file ) {

		// Allows to filter specific files on-the-fly which can be helpful
		// when desiging a new test case without having the run through all the
		// existing tests.
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
	 * @see ByJsonTestCaseProvider::getTestCaseLocation
	 */
	protected function getRequiredJsonTestCaseMinVersion() {
		return '2';
	}

	/**
	 * @see ByJsonTestCaseProvider::runTestCaseFile
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->checkEnvironmentToSkipCurrentTest( $jsonTestCaseFileHandler );

		// Setup
		$this->doTestSetup( $jsonTestCaseFileHandler );

		// Before test execution
		$this->doRunBeforeTest( $jsonTestCaseFileHandler );

		// Run test cases
		$this->doRunParserTests( $jsonTestCaseFileHandler );
		$this->doRunSpecialTests( $jsonTestCaseFileHandler );
		$this->doRunRdfTests( $jsonTestCaseFileHandler );
		$this->doRunQueryTests( $jsonTestCaseFileHandler );
	}

	private function doTestSetup( $jsonTestCaseFileHandler ) {

		$permittedSettings = array(
			'smwgNamespacesWithSemanticLinks',
			'smwgPageSpecialProperties',
			'smwgNamespace',
			'smwgExportBCNonCanonicalFormUse',
			'smwgExportBCAuxiliaryUse',
			'smwgQMaxSize',
			'smwStrictComparators',
			'smwgQSubpropertyDepth',
			'smwgQSubcategoryDepth',
			'smwgQConceptCaching',
			'smwgEnabledInTextAnnotationParserStrictMode',
			'smwgMaxNonExpNumber',
			'smwgDVFeatures',
			'smwgEnabledQueryDependencyLinksStore',
			'smwgEnabledFulltextSearch',
			'smwgFulltextDeferredUpdate',

			// MW related
			'wgLanguageCode',
			'wgContLang',
			'wgLang',
			'wgCapitalLinks',
			'wgAllowDisplayTitle',
			'wgRestrictDisplayTitle'
		);

		foreach ( $permittedSettings as $key ) {
			$this->changeGlobalSettingTo(
				$key,
				$jsonTestCaseFileHandler->getSettingsFor( $key )
			);
		}

		$this->createPagesFor(
			$jsonTestCaseFileHandler->getPageCreationSetupList(),
			NS_MAIN
		);

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	private function doRunBeforeTest( $jsonTestCaseFileHandler ) {

		foreach ( $jsonTestCaseFileHandler->findTaskBeforeTestExecutionByType( 'maintenance-run' ) as $runner => $options ) {
			$maintenanceRunner = UtilityFactory::getInstance()->newRunnerFactory()->newMaintenanceRunner(
				$runner
			);

			$maintenanceRunner->setOptions(
				(array)$options
			);

			$maintenanceRunner->setQuiet()->run();
		}

		foreach ( $jsonTestCaseFileHandler->findTaskBeforeTestExecutionByType( 'job-run' ) as $jobType ) {
			$jobQueueRunner = UtilityFactory::getInstance()->newRunnerFactory()->newJobQueueRunner(
				$jobType
			);

			$jobQueueRunner->run();
		}
	}

	private function doRunParserTests( $jsonTestCaseFileHandler ) {

		$this->parserTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'parser' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->parserTestCaseProcessor->process( $case );
		}
	}

	private function doRunSpecialTests( $jsonTestCaseFileHandler ) {

		$this->specialPageTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'special' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->specialPageTestCaseProcessor->process( $case );
		}
	}

	private function doRunRdfTests( $jsonTestCaseFileHandler ) {

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

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'rdf' ) as $case ) {
			$this->rdfTestCaseProcessor->process( $case );
		}
	}

	private function doRunQueryTests( $jsonTestCaseFileHandler ) {

		// Set query parser late to ensure that expected settings are adjusted
		// (language etc.) because the __construct relies on the context language
		$this->queryTestCaseProcessor->setQueryParser(
			ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser()
		);

		$this->queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'query' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->queryTestCaseProcessor->processQueryCase( new QueryTestCaseInterpreter( $case ) );
		}

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'concept' ) as $conceptCase ) {
			$this->queryTestCaseProcessor->processConceptCase( new QueryTestCaseInterpreter( $conceptCase ) );
		}

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'format' ) as $formatCase ) {
			$this->queryTestCaseProcessor->processFormatCase( new QueryTestCaseInterpreter( $formatCase ) );
		}
	}

}
