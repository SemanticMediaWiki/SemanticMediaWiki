<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\Listener\EventListener\EventHandler;
use SMW\SPARQLStore\TurtleTriplesBuilder;
use SMW\Tests\Utils\JSONScript\ParserTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\ParserHtmlTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\SpecialPageTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\QueryTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\QueryTestCaseInterpreter;
use SMW\Tests\Utils\JSONScript\RdfTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\ApiTestCaseProcessor;
use SMW\Tests\Utils\JSONScript\JsonTestCaseFileHandler;

/**
 * It is provided for external extensions that seek a simple way of creating tests
 * with (or without) Semantic MediaWiki integration in mind.
 *
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
abstract class JSONScriptServicesTestCaseRunner extends JSONScriptTestCaseRunner {

	/**
	 * @var ValidatorFactory
	 */
	protected $validatorFactory;

	/**
	 * @var RunnerFactory
	 */
	protected $runnerFactory;

	/**
	 * @var ApiFactory
	 */
	private $apiFactory;

	/**
	 * @see JSONScriptTestCaseRunner::$deletePagesOnTearDown
	 */
	protected $deletePagesOnTearDown = true;

	/**
	 * Defines set of assertation services to be available by the runner
	 */
	protected $defaultAssertionTypes = [
		'parser',
		'parser-html',
		'special'
	];

	protected function setUp() : void {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->runnerFactory = $utilityFactory->newRunnerFactory();
		$this->validatorFactory = $utilityFactory->newValidatorFactory();
		$this->apiFactory = $utilityFactory->newMwApiFactory();

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

		$this->testEnvironment->resetMediaWikiService( 'NamespaceInfo' );

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
	 * @param string $type
	 *
	 * @return bool
	 */
	protected function runTestAssertionForType( string $type ) : bool {
		return in_array( $type, $this->defaultAssertionTypes );
	}

	/**
	 * @see JSONScriptTestCaseRunner::runTestCaseFile
	 *
	 * @param JsonTestCaseFileHandler $jsonTestCaseFileHandler
	 */
	protected function runTestCaseFile( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$this->checkEnvironmentToSkipCurrentTest( $jsonTestCaseFileHandler );

		// Setup
		$this->prepareTest( $jsonTestCaseFileHandler );

		// Before test execution
		$this->doRunBeforeTest( $jsonTestCaseFileHandler );

		// For some reason there are some random failures where
		// the instance hasn't reset the cache in time to fetch the
		// property definition
		$eventDispatcher = EventHandler::getInstance()->getEventDispatcher();

		$eventDispatcher->dispatch( 'exporter.reset' );
		$eventDispatcher->dispatch( 'query.comparator.reset' );

		// Run test cases
		if ( $this->runTestAssertionForType( 'parser' ) ) {
			$this->doRunParserTests( $jsonTestCaseFileHandler );
		}

		if ( $this->runTestAssertionForType( 'parser-html' ) ) {
			$this->doRunParserHtmlTests( $jsonTestCaseFileHandler );
		}

		if ( $this->runTestAssertionForType( 'special' ) ) {
			$this->doRunSpecialTests( $jsonTestCaseFileHandler );
		}

		if ( $this->runTestAssertionForType( 'rdf' ) ) {
			$this->doRunRdfTests( $jsonTestCaseFileHandler );
		}

		if ( $this->runTestAssertionForType( 'query' ) ) {
			$this->doRunQueryStackTests( $jsonTestCaseFileHandler );
		}

		if ( $this->runTestAssertionForType( 'api' ) ) {
			$this->doRunApiTests( $jsonTestCaseFileHandler );
		}
	}

	/**
	 * @see JSONScriptTestCaseRunner::getPermittedSettings
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

		// Config isolation causes NamespaceInfo to not access the `MainConfig`
		// therefore reset the services so that it copies the changed setting.
		// https://github.com/wikimedia/mediawiki/commit/7ada64684e6477be44405dedbfdb0d96242f2e73
		$capitalLinks = function( $val ) {
			$this->testEnvironment->resetMediaWikiService( 'NamespaceInfo' );
			return $val;
		};

		$this->registerConfigValueCallback( 'wgCapitalLinks', $capitalLinks );

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
			'smwgQMaxInlineLimit',
			'smwgElasticsearchConfig',
			'smwgDefaultNumRecurringEvents',
			'smwgMandatorySubpropertyParentTypeInheritance',
			'smwgPlainList',
			'smwgMaxPropertyValues',

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

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'parser' );

		if ( $testCases === [] ) {
			return;
		}

		$parserTestCaseProcessor = new ParserTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newSemanticDataValidator(),
			$this->validatorFactory->newIncomingSemanticDataValidator( $this->getStore() ),
			$this->validatorFactory->newStringValidator()
		);

		$parserTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$parserTestCaseProcessor->process( $case );
		}
	}

	private function doRunParserHtmlTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'parser-html' );

		if ( $testCases === [] ) {
			return;
		}

		$parserHtmlTestCaseProcessor = new ParserHtmlTestCaseProcessor(
			$this->validatorFactory->newHtmlValidator()
		);

		if ( !$parserHtmlTestCaseProcessor->canUse() ) {
			$this->markTestIncomplete(
				'The required resource for the ParserHtmlTestCaseProcessor/HtmlValidator is not available.'
			);
		}

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$parserHtmlTestCaseProcessor->process( $case );
		}
	}

	private function doRunSpecialTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'special' );

		if ( $testCases === [] ) {
			return;
		}

		$specialPageTestCaseProcessor = new SpecialPageTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newStringValidator()
		);

		$specialPageTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		$specialPageTestCaseProcessor->setTestCaseLocation(
			$this->getTestCaseLocation()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$specialPageTestCaseProcessor->process( $case );
		}
	}

	private function doRunRdfTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'rdf' );

		if ( $testCases === [] ) {
			return;
		}

		$rdfTestCaseProcessor = new RdfTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newStringValidator(),
			$this->runnerFactory
		);

		$rdfTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {
			$rdfTestCaseProcessor->process( $case );
		}
	}

	private function doRunQueryStackTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		// Set query parser late to ensure that expected settings are adjusted
		// (language etc.) because the __construct relies on the context language
		$queryParser = ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser();

		$i = 0;
		$count = 0;

		$this->doRunQueryTests( $jsonTestCaseFileHandler, $queryParser, $i, $count );
		$this->doRunConceptTests( $jsonTestCaseFileHandler, $queryParser, $i, $count );
		$this->doRunFormatTests( $jsonTestCaseFileHandler, $queryParser, $i, $count );

		// Avoid tests being marked as risky when all cases were skipped
		if ( $i == 0 && $count > 0 ) {
			$this->markTestSkipped( 'Skipped all assertions for: ' . $this->getName() );
		}
	}

	private function doRunQueryTests( $jsonTestCaseFileHandler, $queryParser, &$i, &$count ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'query' );
		$count += count( $testCases );

		if ( $testCases === [] ) {
			return;
		}

		$queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newQueryResultValidator(),
			$this->validatorFactory->newStringValidator(),
			$this->validatorFactory->newNumberValidator()
		);

		$queryTestCaseProcessor->setQueryParser(
			$queryParser
		);

		$queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$queryTestCaseProcessor->processQueryCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}
	}

	private function doRunConceptTests( $jsonTestCaseFileHandler, $queryParser, &$i, &$count ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'concept' );
		$count += count( $testCases );

		if ( $testCases === [] ) {
			return;
		}

		$queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newQueryResultValidator(),
			$this->validatorFactory->newStringValidator(),
			$this->validatorFactory->newNumberValidator()
		);

		$queryTestCaseProcessor->setQueryParser(
			$queryParser
		);

		$queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {
			$queryTestCaseProcessor->processConceptCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}
	}

	private function doRunFormatTests( $jsonTestCaseFileHandler, $queryParser, &$i, &$count ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'format' );
		$count += count( $testCases );

		if ( $testCases === [] ) {
			return;
		}

		$queryTestCaseProcessor = new QueryTestCaseProcessor(
			$this->getStore(),
			$this->validatorFactory->newQueryResultValidator(),
			$this->validatorFactory->newStringValidator(),
			$this->validatorFactory->newNumberValidator()
		);

		$queryTestCaseProcessor->setQueryParser(
			$queryParser
		);

		$queryTestCaseProcessor->setDebugMode(
			$jsonTestCaseFileHandler->getDebugMode()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$queryTestCaseProcessor->processFormatCase( new QueryTestCaseInterpreter( $case ) );
			$i++;
		}
	}

	private function doRunApiTests( JsonTestCaseFileHandler $jsonTestCaseFileHandler ) {

		$testCases = $jsonTestCaseFileHandler->findTestCasesByType( 'api' );

		if ( $testCases === [] ) {
			return;
		}

		$apiTestCaseProcessor = new ApiTestCaseProcessor(
			$this->apiFactory,
			$this->validatorFactory->newStringValidator()
		);

		$apiTestCaseProcessor->setTestCaseLocation(
			$this->getTestCaseLocation()
		);

		foreach ( $testCases as $case ) {

			if ( $jsonTestCaseFileHandler->requiredToSkipFor( $case, $this->connectorId ) ) {
				continue;
			}

			$apiTestCaseProcessor->process( $case );
		}
	}

}
