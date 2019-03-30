<?php

namespace SMW\Tests\Integration\JSONScript;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\EventHandler;
use SMW\PropertySpecificationLookup;
use SMW\SPARQLStore\TurtleTriplesBuilder;
use SMW\Tests\JsonTestCaseFileHandler;
use SMW\Tests\JsonTestCaseScriptRunner;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class JsonTestCaseScriptRunnerTest extends JsonTestCaseScriptRunner {

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
	 * @var ParserHtmlTestCaseProcessor
	 */
	private $parserHtmlTestCaseProcessor;

	/**
	 * @var ApiTestCaseProcessor
	 */
	private $apiTestCaseProcessor;

	/**
	 * @var RunnerFactory
	 */
	private $runnerFactory;

	/**
	 * @var EventDispatcher
	 */
	private $eventDispatcher;

	/**
	 * @see JsonTestCaseScriptRunner::$deletePagesOnTearDown
	 */
	protected $deletePagesOnTearDown = true;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();
		$this->runnerFactory = $utilityFactory->newRunnerFactory();

		$validatorFactory = $utilityFactory->newValidatorFactory();
		$stringValidator = $validatorFactory->newStringValidator();

		$this->queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$validatorFactory->newQueryResultValidator(),
			$stringValidator,
			$validatorFactory->newNumberValidator()
		);

		$this->rdfTestCaseProcessor = new RdfTestCaseProcessor(
			$this->getStore(),
			$stringValidator,
			$this->runnerFactory
		);

		$this->parserTestCaseProcessor = new ParserTestCaseProcessor(
			$this->getStore(),
			$validatorFactory->newSemanticDataValidator(),
			$validatorFactory->newIncomingSemanticDataValidator( $this->getStore() ),
			$stringValidator
		);

		$this->specialPageTestCaseProcessor = new SpecialPageTestCaseProcessor(
			$this->getStore(),
			$stringValidator
		);

		$this->parserHtmlTestCaseProcessor = new ParserHtmlTestCaseProcessor(
			$validatorFactory->newHtmlValidator()
		);

		$this->apiTestCaseProcessor = new ApiTestCaseProcessor(
			$utilityFactory->newMwApiFactory(),
			$stringValidator
		);

		$this->eventDispatcher = EventHandler::getInstance()->getEventDispatcher();

		// This ensures that if content is created in the NS_MEDIAWIKI namespace
		// and an object relies on the MediaWikiNsContentReader then it uses the DB
		ApplicationFactory::clear();
		ApplicationFactory::getInstance()->getMediaWikiNsContentReader()->skipMessageCache();
		DataValueFactory::getInstance()->clear();

		// Reset the Title/TitleParser otherwise a singleton instance holds an outdated
		// content language reference
		$this->testEnvironment->resetMediaWikiService( '_MediaWikiTitleCodec' );
		$this->testEnvironment->resetMediaWikiService( 'TitleParser' );

		// #3414
		// NameTableAccessException: Expected unused ID from database insert for
		// 'mw-changed-redirect-target'  into 'change_tag_def',
		$this->testEnvironment->resetMediaWikiService( 'NameTableStoreFactory' );

		$this->testEnvironment->resetPoolCacheById( TurtleTriplesBuilder::POOLCACHE_ID );

		// Make sure LocalSettings don't interfere with the default settings
		$this->testEnvironment->withConfiguration(
			[
				'smwgQueryResultCacheType' => false,
				'smwgQFilterDuplicates' => false,
				'smwgExportResourcesAsIri' => false,
				'smwgCompactLinkSupport' => false,
				'smwgEnabledFulltextSearch' => false,
				'smwgSparqlReplicationPropertyExemptionList' => [],
				'smwgPageSpecialProperties' => [ '_MDAT' ],
				'smwgFieldTypeFeatures' => SMW_FIELDT_NONE,
				'smwgDVFeatures' => $GLOBALS['smwgDVFeatures'] & ~SMW_DV_NUMV_USPACE,
				'smwgCacheUsage' => [
					'api.browse' => false
				] + $GLOBALS['smwgCacheUsage']
			]
		);
	}

	/**
	 * @see JsonTestCaseScriptRunner::getTestCaseLocation
	 */
	protected function getTestCaseLocation() {
		return __DIR__ . '/TestCases';
	}

	/**
	 * @see JsonTestCaseScriptRunner::getTestCaseLocation
	 */
	protected function getRequiredJsonTestCaseMinVersion() {
		return '2';
	}

	/**
	 * @see JsonTestCaseScriptRunner::getAllowedTestCaseFiles
	 */
	protected function getAllowedTestCaseFiles() {
		return [];
	}

	/**
	 * @see JsonTestCaseScriptRunner::getDependencyDefinitions
	 */
	protected function getDependencyDefinitions() {
		return [
			'Maps' => function( $val, &$reason ) {

				if ( !defined( 'SM_VERSION' ) ) {
					$reason = "Dependency: Maps (or Semantic Maps) as requirement is not available!";
					return false;
				}

				list( $compare, $requiredVersion ) = explode( ' ', $val );
				$version = SM_VERSION;

				if ( !version_compare( $version, $requiredVersion, $compare ) ) {
					$reason = "Dependency: Required version of Maps ($requiredVersion $compare $version) is not available!";
					return false;
				}

				return true;
			}
		];
	}

	/**
	 * @see JsonTestCaseScriptRunner::runTestCaseFile
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->checkEnvironmentToSkipCurrentTest( $jsonTestCaseFileHandler );

		// Setup
		$this->prepareTest( $jsonTestCaseFileHandler );

		// Before test execution
		$this->doRunBeforeTest( $jsonTestCaseFileHandler );

		// Run test cases
		$this->doRunParserTests( $jsonTestCaseFileHandler );
		$this->doRunSpecialTests( $jsonTestCaseFileHandler );
		$this->doRunRdfTests( $jsonTestCaseFileHandler );
		$this->doRunQueryTests( $jsonTestCaseFileHandler );
		$this->doRunParserHtmlTests( $jsonTestCaseFileHandler );
		$this->doRunApiTests( $jsonTestCaseFileHandler );
	}

	/**
	 * @see JsonTestCaseScriptRunner::getPermittedSettings
	 */
	protected function getPermittedSettings() {
		parent::getPermittedSettings();

		$elasticsearchConfig = function( $val ) {

			if ( $this->getStore() instanceof \SMWElasticStore ) {
				$config = $this->getStore()->getConnection( 'elastic' )->getConfig();

				foreach ( $val as $key => $value ) {
					$config->set( $key, array_merge( $config->get( $key ), $value ) );
				}

				return $config->toArray();
			}
		};

		$this->registerConfigValueCallback( 'smwgElasticsearchConfig', $elasticsearchConfig );

		return [
			'smwgNamespacesWithSemanticLinks',
			'smwgPageSpecialProperties',
			'smwgNamespace',
			'smwgExportBCNonCanonicalFormUse',
			'smwgExportBCAuxiliaryUse',
			'smwgExportResourcesAsIri',
			'smwgQMaxSize',
			'smwgQMaxDepth',
			'smwStrictComparators',
			'smwgQSubpropertyDepth',
			'smwgQSubcategoryDepth',
			'smwgQConceptCaching',
			'smwgMaxNonExpNumber',
			'smwgDVFeatures',
			'smwgEnabledQueryDependencyLinksStore',
			'smwgEnabledFulltextSearch',
			'smwgFulltextDeferredUpdate',
			'smwgFulltextSearchIndexableDataTypes',
			'smwgFixedProperties',
			'smwgPropertyZeroCountDisplay',
			'smwgQueryResultCacheType',
			'smwgLinksInValues',
			'smwgQFilterDuplicates',
			'smwgQueryProfiler',
			'smwgEntityCollation',
			'smwgSparqlQFeatures',
			'smwgQExpensiveThreshold',
			'smwgQExpensiveExecutionLimit',
			'smwgFieldTypeFeatures',
			'smwgCreateProtectionRight',
			'smwgParserFeatures',
			'smwgCategoryFeatures',
			'smwgDefaultOutputFormatters',
			'smwgCompactLinkSupport',
			'smwgCacheUsage',
			'smwgQSortFeatures',
			'smwgElasticsearchConfig',
			'smwgDefaultNumRecurringEvents',
			'smwgMandatorySubpropertyParentTypeInheritance',

			// MW related
			'wgLanguageCode',
			'wgContLang',
			'wgLang',
			'wgCapitalLinks',
			'wgAllowDisplayTitle',
			'wgRestrictDisplayTitle',
			'wgSearchType',
			'wgEnableUploads',
			'wgFileExtensions',
			'wgDefaultUserOptions',
			'wgLocalTZoffset'
		];
	}

	private function prepareTest( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		foreach ( $this->getPermittedSettings() as $key ) {
			$this->changeGlobalSettingTo(
				$key,
				$jsonTestCaseFileHandler->getSettingsFor( $key, $this->getConfigValueCallback( $key ) )
			);
		}

		if ( $jsonTestCaseFileHandler->hasSetting( 'smwgFieldTypeFeatures' ) ) {
			$this->doRunTableSetupBeforeContentCreation();
		}

		// #2135
		// On some occasions (e.g. fixed properties) and to setup the correct
		// table schema, run the creation once before the content is created
		$pageList = $jsonTestCaseFileHandler->getPageCreationSetupList();

		if ( $jsonTestCaseFileHandler->hasSetting( 'smwgFixedProperties' ) ) {
			foreach ( $pageList as $page ) {
				if ( isset( $page['namespace'] ) && $page['namespace'] === 'SMW_NS_PROPERTY' ) {
					$this->createPagesFrom( [ $page ] );
				}
			}

			$this->doRunTableSetupBeforeContentCreation();
		}

		$this->createPagesFrom(
			$pageList,
			NS_MAIN
		);
	}

	private function doRunTableSetupBeforeContentCreation( $pageList = null ) {

		if ( $pageList !== null ) {
			$this->createPagesFrom( $pageList );
		}

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( 'setupStore' );
		$maintenanceRunner->setQuiet();
		$maintenanceRunner->run();
	}

	private function doRunBeforeTest( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		foreach ( $jsonTestCaseFileHandler->findTasksBeforeTestExecutionByType( 'maintenance-run' ) as $runner => $options ) {

			$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( $runner );
			$maintenanceRunner->setQuiet();

			$maintenanceRunner->setOptions(
				(array)$options
			);

			$maintenanceRunner->run();

			if ( isset( $options['quiet'] ) && $options['quiet'] === false ) {
				print_r( $maintenanceRunner->getOutput() );
			}
		}

		foreach ( $jsonTestCaseFileHandler->findTasksBeforeTestExecutionByType( 'job-run' ) as $jobType ) {
			$jobQueueRunner = $this->runnerFactory->newJobQueueRunner( $jobType );
			$jobQueueRunner->run();
		}

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	private function doRunParserTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

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

	private function doRunSpecialTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->specialPageTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		$this->specialPageTestCaseProcessor->setTestCaseLocation(
			$this->getTestCaseLocation()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'special' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->specialPageTestCaseProcessor->process( $case );
		}
	}

	private function doRunRdfTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

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

	private function doRunQueryTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		// Set query parser late to ensure that expected settings are adjusted
		// (language etc.) because the __construct relies on the context language
		$this->queryTestCaseProcessor->setQueryParser(
			ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser()
		);

		$this->queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		$i = 0;
		$count = 0;

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'query' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->queryTestCaseProcessor->processQueryCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'concept' ) as $conceptCase ) {
			$this->queryTestCaseProcessor->processConceptCase( new QueryTestCaseInterpreter( $conceptCase ) );
			$i++;
		}

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'format' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->queryTestCaseProcessor->processFormatCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}

		$count += $jsonTestCaseFileHandler->countTestCasesByType( 'query' );
		$count += $jsonTestCaseFileHandler->countTestCasesByType( 'concept' );
		$count += $jsonTestCaseFileHandler->countTestCasesByType( 'format' );

		// Avoid tests being marked as risky when all cases were skipped
		if ( $i == 0 && $count > 0 ) {
			$this->markTestSkipped( 'Skipped all assertions for: ' . $this->getName() );
		}
	}

	/**
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	private function doRunParserHtmlTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		if ( !$this->parserHtmlTestCaseProcessor->canUse() ) {
			$this->markTestIncomplete( 'The required resource for the ParserHtmlTestCaseProcessor/HtmlValidator is not available.' );
		}

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'parser-html' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->parserHtmlTestCaseProcessor->process( $case );
		}
	}

	/**
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	private function doRunApiTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->apiTestCaseProcessor->setTestCaseLocation(
			$this->getTestCaseLocation()
		);

		foreach ( $jsonTestCaseFileHandler->findTestCasesByType( 'api' ) as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$this->apiTestCaseProcessor->process( $case );
		}
	}

}
