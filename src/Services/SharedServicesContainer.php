<?php

namespace SMW\Services;

use JsonSchema\Validator as SchemaValidator;
use Onoi\BlobStore\BlobStore;
use Onoi\CallbackContainer\CallbackContainer;
use Onoi\CallbackContainer\ContainerBuilder;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\Constraint\ConstraintErrorIndicatorProvider;
use SMW\ConstraintFactory;
use SMW\ContentParser;
use SMW\DataItemFactory;
use SMW\DependencyValidator;
use SMW\DisplayTitleFinder;
use SMW\Elastic\ElasticFactory;
use SMW\EntityCache;
use SMW\Factbox\FactboxFactory;
use SMW\HierarchyLookup;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Localizer;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Database;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\MediaWiki\TitleFactory;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\RevisionGuard;
use SMW\MediaWiki\IndicatorRegistry;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\Services\DataValueServiceFactory;
use SMW\MessageFormatter;
use SMW\NamespaceExaminer;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\Parser\LinksProcessor;
use SMW\ParserData;
use SMW\PostProcHandler;
use SMW\Property\AnnotatorFactory;
use SMW\PropertyLabelFinder;
use SMW\PropertyRestrictionExaminer;
use SMW\PropertySpecificationLookup;
use SMW\Protection\EditProtectionUpdater;
use SMW\Protection\ProtectionValidator;
use SMW\Query\Cache\CacheStats;
use SMW\Query\Cache\ResultCache;
use SMW\Query\Processor\ParamListProcessor;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\Schema\SchemaFactory;
use SMW\Settings;
use SMW\SetupFile;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Utils\JsonSchemaValidator;
use SMW\Utils\Stats;
use SMW\Utils\TempFile;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SharedServicesContainer implements CallbackContainer {

	/**
	 * @see CallbackContainer::register
	 *
	 * @since 2.3
	 */
	public function register( ContainerBuilder $containerBuilder ) {

		$containerBuilder->registerCallback( 'Store', [ $this, 'newStore' ] );
		$containerBuilder->registerCallback( 'IndicatorRegistry', [ $this, 'newIndicatorRegistry' ] );

		$this->registerCallbackHandlers( $containerBuilder );
		$this->registerCallableFactories( $containerBuilder );
		$this->registerCallbackHandlersByConstructedInstance( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @return Store
	 */
	public function newStore( ContainerBuilder $containerBuilder, $storeClass = null ) {

		$containerBuilder->registerExpectedReturnType( 'Store', '\SMW\Store' );
		$settings = $containerBuilder->singleton( 'Settings' );

		if ( $storeClass === null || $storeClass === '' ) {
			$storeClass = $settings->get( 'smwgDefaultStore' );
		}

		$store = StoreFactory::getStore( $storeClass );

		$configs = [
			'smwgDefaultStore',
			'smwgAutoRefreshSubject',
			'smwgEnableUpdateJobs',
			'smwgQEqualitySupport',
			'smwgElasticsearchConfig'
		];

		foreach ( $configs as $config ) {
			$store->setOption( $config, $settings->get( $config ) );
		}

		$store->setLogger(
			$containerBuilder->singleton( 'MediaWikiLogger' )
		);

		return $store;
	}

	/**
	 * @since 3.1
	 *
	 * @return IndicatorRegistry
	 */
	public function newIndicatorRegistry( ContainerBuilder $containerBuilder ) {

		$indicatorRegistry = new IndicatorRegistry();
		$entityExaminerIndicatorsFactory = new EntityExaminerIndicatorsFactory();

		$entityExaminerIndicatorProvider = $entityExaminerIndicatorsFactory->newEntityExaminerIndicatorProvider(
			$containerBuilder->singleton( 'Store', null )
		);

		$indicatorRegistry->addIndicatorProvider(
			$entityExaminerIndicatorProvider
		);

		return $indicatorRegistry;
	}

	private function registerCallbackHandlers( ContainerBuilder $containerBuilder ) {

		$containerBuilder->registerCallback( 'Settings', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'Settings', '\SMW\Settings' );

			$settings = new Settings();

			$settings->setHookDispatcher(
				$containerBuilder->singleton( 'HookDispatcher' )
			);

			$settings->loadFromGlobals();

			return $settings;
		} );

		/**
		 * ConnectionManager
		 *
		 * @return callable
		 */
		$containerBuilder->registerCallback( 'ConnectionManager', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ConnectionManager', ConnectionManager::class );
			return new ConnectionManager();
		} );

		/**
		 * SetupFile
		 *
		 * @return callable
		 */
		$containerBuilder->registerCallback( 'SetupFile', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'SetupFile', SetupFile::class );
			return new SetupFile();
		} );

		$containerBuilder->registerCallback( 'Cache', function( $containerBuilder, $cacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'Cache', '\Onoi\Cache\Cache' );
			return $containerBuilder->create( 'CacheFactory' )->newMediaWikiCompositeCache( $cacheType );
		} );

		$containerBuilder->registerCallback( 'NamespaceExaminer', function() use ( $containerBuilder ) {

			$settings = $containerBuilder->singleton( 'Settings' );
			$namespaceInfo = $containerBuilder->singleton( 'NamespaceInfo' );

			$namespaceExaminer = new NamespaceExaminer(
				$settings->get( 'smwgNamespacesWithSemanticLinks' )
			);

			$namespaceExaminer->setValidNamespaces(
				$namespaceInfo->getValidNamespaces()
			);

			return $namespaceExaminer;
		} );

		$containerBuilder->registerCallback( 'ParserData', function( $containerBuilder, \Title $title, \ParserOutput $parserOutput ) {
			$containerBuilder->registerExpectedReturnType( 'ParserData', ParserData::class );

			$parserData = new ParserData( $title, $parserOutput );

			$parserData->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			return $parserData;
		} );

		$containerBuilder->registerCallback( 'LinksProcessor', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'LinksProcessor', '\SMW\Parser\LinksProcessor' );
			return new LinksProcessor();
		} );

		$containerBuilder->registerCallback( 'MessageFormatter', function( $containerBuilder, \Language $language ) {
			$containerBuilder->registerExpectedReturnType( 'MessageFormatter', '\SMW\MessageFormatter' );
			return new MessageFormatter( $language );
		} );

		$containerBuilder->registerCallback( 'MediaWikiNsContentReader', function() use ( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'MediaWikiNsContentReader', '\SMW\MediaWiki\MediaWikiNsContentReader' );

			$mediaWikiNsContentReader = new MediaWikiNsContentReader();

			$mediaWikiNsContentReader->setRevisionGuard(
				$containerBuilder->singleton( 'RevisionGuard' )
			);

			return $mediaWikiNsContentReader;
		} );

		$containerBuilder->registerCallback( 'PageCreator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PageCreator', '\SMW\MediaWiki\PageCreator' );
			return new PageCreator();
		} );

		$containerBuilder->registerCallback( 'PageUpdater', function( $containerBuilder, $connection, TransactionalCallableUpdate $transactionalCallableUpdate = null ) {
			$containerBuilder->registerExpectedReturnType( 'PageUpdater', '\SMW\MediaWiki\PageUpdater' );
			return new PageUpdater( $connection, $transactionalCallableUpdate );
		} );

		$containerBuilder->registerCallback( 'EntityCache', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'EntityCache', '\SMW\EntityCache' );
			return new EntityCache( $containerBuilder->singleton( 'Cache', $GLOBALS['smwgMainCacheType'] ) );
		} );

		/**
		 * JobQueue
		 *
		 * @return callable
		 */
		$containerBuilder->registerCallback( 'JobQueue', function( $containerBuilder ) {

			$containerBuilder->registerExpectedReturnType(
				'JobQueue',
				'\SMW\MediaWiki\JobQueue'
			);

			return new JobQueue(
				$containerBuilder->create( 'JobQueueGroup' )
			);
		} );

		$containerBuilder->registerCallback( 'ManualEntryLogger', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ManualEntryLogger', '\SMW\MediaWiki\ManualEntryLogger' );
			return new ManualEntryLogger();
		} );

		$containerBuilder->registerCallback( 'TitleFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'TitleFactory', '\SMW\MediaWiki\TitleFactory' );
			return new TitleFactory();
		} );

		$containerBuilder->registerCallback( 'HookDispatcher', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'HookDispatcher', HookDispatcher::class );
			return new HookDispatcher();
		} );

		$containerBuilder->registerCallback( 'RevisionGuard', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'RevisionGuard', RevisionGuard::class );

			$revisionGuard = new RevisionGuard(
				$containerBuilder->create( 'RevisionLookup' )
			);

			$revisionGuard->setHookDispatcher(
				$containerBuilder->singleton( 'HookDispatcher' )
			);

			return $revisionGuard;
		} );

		$containerBuilder->registerCallback( 'ContentParser', function( $containerBuilder, \Title $title ) {
			$containerBuilder->registerExpectedReturnType( 'ContentParser', '\SMW\ContentParser' );

			$contentParser = new ContentParser(
				$title,
				$containerBuilder->create( 'Parser' )
			);

			$contentParser->setRevisionGuard(
				$containerBuilder->singleton( 'RevisionGuard' )
			);

			return $contentParser;
		} );

		$containerBuilder->registerCallback( 'DeferredCallableUpdate', function( $containerBuilder, callable $callback = null ) {
			$containerBuilder->registerExpectedReturnType( 'DeferredCallableUpdate', '\SMW\MediaWiki\Deferred\CallableUpdate' );
			$containerBuilder->registerAlias( 'CallableUpdate', CallableUpdate::class );

			return new CallableUpdate( $callback );
		} );

		$containerBuilder->registerCallback( 'DeferredTransactionalCallableUpdate', function( $containerBuilder, callable $callback = null, Database $connection = null ) {
			$containerBuilder->registerExpectedReturnType( 'DeferredTransactionalUpdate', '\SMW\MediaWiki\Deferred\TransactionalCallableUpdate' );
			$containerBuilder->registerAlias( 'DeferredTransactionalUpdate', TransactionalCallableUpdate::class );

			return new TransactionalCallableUpdate( $callback, $connection );
		} );

		/**
		 * @var InMemoryPoolCache
		 */
		$containerBuilder->registerCallback( 'InMemoryPoolCache', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'InMemoryPoolCache', '\SMW\InMemoryPoolCache' );
			return InMemoryPoolCache::getInstance();
		} );

		/**
		 * @var AnnotatorFactory
		 */
		$containerBuilder->registerCallback( 'PropertyAnnotatorFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertyAnnotatorFactory', AnnotatorFactory::class );
			return new AnnotatorFactory();
		} );

		/**
		 * @var ConnectionProvider
		 */
		$containerBuilder->registerAlias( 'ConnectionProvider', 'DBConnectionProvider' );

		$containerBuilder->registerCallback( 'ConnectionProvider', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ConnectionProvider', ConnectionProvider::class );

			$connectionProvider = new ConnectionProvider();

			$connectionProvider->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			return $connectionProvider;
		} );

		/**
		 * @var TempFile
		 */
		$containerBuilder->registerCallback( 'TempFile', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'TempFile', '\SMW\Utils\TempFile' );
			return new TempFile();
		} );

		/**
		 * @var PostProcHandler
		 */
		$containerBuilder->registerCallback( 'PostProcHandler', function( $containerBuilder, \ParserOutput $parserOutput ) {
			$containerBuilder->registerExpectedReturnType( 'PostProcHandler', PostProcHandler::class );

			$settings = $containerBuilder->singleton( 'Settings' );

			$postProcHandler = new PostProcHandler(
				$parserOutput,
				$containerBuilder->singleton( 'Cache' )
			);

			$postProcHandler->setOptions(
				$settings->get( 'smwgPostEditUpdate' ) +
				[ 'smwgEnabledQueryDependencyLinksStore' => $settings->get( 'smwgEnabledQueryDependencyLinksStore' ) ] +
				[ 'smwgEnabledFulltextSearch' => $settings->get( 'smwgEnabledFulltextSearch' ) ]
			);

			return $postProcHandler;
		} );

		/**
		 * @var JsonSchemaValidator
		 */
		$containerBuilder->registerCallback( 'JsonSchemaValidator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'JsonSchemaValidator', JsonSchemaValidator::class );
			$containerBuilder->registerAlias( 'JsonSchemaValidator', JsonSchemaValidator::class );

			$schemaValidator = null;

			// justinrainbow/json-schema
			if ( class_exists( SchemaValidator::class ) ) {
				$schemaValidator = new SchemaValidator();
			}

			$jsonSchemaValidator = new JsonSchemaValidator(
				$schemaValidator
			);

			return $jsonSchemaValidator;
		} );

		/**
		 * @var SchemaFactory
		 */
		$containerBuilder->registerCallback( 'SchemaFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'SchemaFactory', SchemaFactory::class );

			$settings = $containerBuilder->singleton( 'Settings' );

			$schemaFactory = new SchemaFactory(
				$settings->get( 'smwgSchemaTypes' )
			);

			return $schemaFactory;
		} );

		/**
		 * @var ConstraintFactory
		 */
		$containerBuilder->registerCallback( 'ConstraintFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ConstraintFactory', ConstraintFactory::class );
			return new ConstraintFactory();
		} );

		/**
		 * @var ElasticFactory
		 */
		$containerBuilder->registerCallback( 'ElasticFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ElasticFactory', ElasticFactory::class );
			return new ElasticFactory();
		} );

		/**
		 * @var QueryCreator
		 */
		$containerBuilder->registerCallback( 'QueryCreator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'QueryCreator', QueryCreator::class );

			$settings = $containerBuilder->singleton( 'Settings' );

			$queryCreator = new QueryCreator(
				$containerBuilder->singleton( 'QueryFactory' ),
				$settings->get( 'smwgQDefaultNamespaces' ),
				$settings->get( 'smwgQDefaultLimit' )
			);

			$queryCreator->setQFeatures(
				$settings->get( 'smwgQFeatures' )
			);

			$queryCreator->setQConceptFeatures(
				$settings->get( 'smwgQConceptFeatures' )
			);

			return $queryCreator;
		} );

		/**
		 * @var ParamListProcessor
		 */
		$containerBuilder->registerCallback( 'ParamListProcessor', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ParamListProcessor', ParamListProcessor::class );

			$paramListProcessor = new ParamListProcessor(
				//$containerBuilder->singleton( 'PrintRequestFactory' )
			);

			return $paramListProcessor;
		} );
	}

	private function registerCallableFactories( ContainerBuilder $containerBuilder ) {

		/**
		 * @var CacheFactory
		 */
		$containerBuilder->registerCallback( 'CacheFactory', function( $containerBuilder, $mainCacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'CacheFactory', '\SMW\CacheFactory' );
			return new CacheFactory( $mainCacheType );
		} );

		/**
		 * @var IteratorFactory
		 */
		$containerBuilder->registerCallback( 'IteratorFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'IteratorFactory', '\SMW\IteratorFactory' );
			return new IteratorFactory();
		} );

		/**
		 * @var JobFactory
		 */
		$containerBuilder->registerCallback( 'JobFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'JobFactory', '\SMW\MediaWiki\JobFactory' );
			return new JobFactory();
		} );

		/**
		 * @var FactboxFactory
		 */
		$containerBuilder->registerCallback( 'FactboxFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'FactboxFactory', '\SMW\Factbox\FactboxFactory' );
			return new FactboxFactory();
		} );

		/**
		 * @var QuerySourceFactory
		 */
		$containerBuilder->registerCallback( 'QuerySourceFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'QuerySourceFactory', '\SMW\Query\QuerySourceFactory' );

			return new QuerySourceFactory(
				$containerBuilder->singleton( 'Store', null ),
				$containerBuilder->singleton( 'Settings' )->get( 'smwgQuerySources' )
			);
		} );

		/**
		 * @var QueryFactory
		 */
		$containerBuilder->registerCallback( 'QueryFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'QueryFactory', '\SMW\QueryFactory' );
			return new QueryFactory();
		} );

		/**
		 * @var DataItemFactory
		 */
		$containerBuilder->registerCallback( 'DataItemFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'DataItemFactory', '\SMW\DataItemFactory' );
			return new DataItemFactory();
		} );

		/**
		 * @var DataValueServiceFactory
		 */
		$containerBuilder->registerCallback( 'DataValueServiceFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'DataValueServiceFactory', '\SMW\Services\DataValueServiceFactory' );

			$containerBuilder->registerFromFile(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgServicesFileDir' ) . '/' . DataValueServiceFactory::SERVICE_FILE
			);

			$dataValueServiceFactory = new DataValueServiceFactory(
				$containerBuilder
			);

			return $dataValueServiceFactory;
		} );

		/**
		 * @var QueryDependencyLinksStoreFactory
		 */
		$containerBuilder->registerCallback( 'QueryDependencyLinksStoreFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'QueryDependencyLinksStoreFactory', '\SMW\SQLStore\QueryDependencyLinksStoreFactory' );
			return new QueryDependencyLinksStoreFactory();
		} );

	}

	private function registerCallbackHandlersByConstructedInstance( ContainerBuilder $containerBuilder ) {

		/**
		 * @var BlobStore
		 */
		$containerBuilder->registerCallback( 'BlobStore', function( $containerBuilder, $namespace, $cacheType = null, $ttl = 0 ) {
			$containerBuilder->registerExpectedReturnType( 'BlobStore', '\Onoi\BlobStore\BlobStore' );

			$cacheFactory = $containerBuilder->create( 'CacheFactory' );

			$blobStore = new BlobStore(
				$namespace,
				$cacheFactory->newMediaWikiCompositeCache( $cacheType )
			);

			$blobStore->setNamespacePrefix(
				$cacheFactory->getCachePrefix()
			);

			$blobStore->setExpiryInSeconds(
				$ttl
			);

			$blobStore->setUsageState(
				$cacheType !== CACHE_NONE && $cacheType !== false
			);

			return $blobStore;
		} );

		/**
		 * @var ResultCache
		 */
		$containerBuilder->registerCallback( 'ResultCache', function( $containerBuilder, $cacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'ResultCache', '\SMW\Query\Cache\ResultCache' );

			$cacheFactory = $containerBuilder->create( 'CacheFactory' );

			$settings = $containerBuilder->singleton( 'Settings' );
			$cacheType = $cacheType === null ? $settings->get( 'smwgQueryResultCacheType' ) : $cacheType;


			// Explicitly use the CACHE_DB to access a SqlBagOstuff instance
			// for a bit more persistence
			$cacheStats = new CacheStats(
				$cacheFactory->newMediaWikiCache( CACHE_DB ),
				ResultCache::STATSD_ID
			);

			$resultCache = new ResultCache(
				$containerBuilder->singleton( 'Store', null ),
				$containerBuilder->singleton( 'QueryFactory' ),
				$containerBuilder->create(
					'BlobStore',
					ResultCache::CACHE_NAMESPACE,
					$cacheType,
					$settings->get( 'smwgQueryResultCacheLifetime' )
				),
				$cacheStats
			);

			$resultCache->setCacheKeyExtension(
				// If the mix of dataTypes changes then modify the hash
				$settings->get( 'smwgFulltextSearchIndexableDataTypes' ) .

				// If the collation is altered then modify the hash as it
				// is likely that the sort order of results change
				$settings->get( 'smwgEntityCollation' ) .

				// Changing the sobj has computation should invalidate
				// existing caches to avoid oudated references SOBJ IDs
				$settings->get( 'smwgUseComparableContentHash' )
			);

			$resultCache->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			$resultCache->setNonEmbeddedCacheLifetime(
				$settings->get( 'smwgQueryResultNonEmbeddedCacheLifetime' )
			);

			return $resultCache;
		} );

		/**
		 * @var Stats
		 */
		$containerBuilder->registerCallback( 'Stats', function( $containerBuilder, $id ) {
			$containerBuilder->registerExpectedReturnType( 'Stats', '\SMW\Utils\Stats' );

			$cacheFactory = $containerBuilder->create( 'CacheFactory' );

			// Explicitly use the DB to access a SqlBagOstuff instance
			$stats = new Stats(
				$cacheFactory->newMediaWikiCache( CACHE_DB ),
				$id
			);

			return $stats;
		} );

		/**
		 * @var PropertySpecificationLookup
		 */
		$containerBuilder->registerCallback( 'PropertySpecificationLookup', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertySpecificationLookup', '\SMW\PropertySpecificationLookup' );

			$contentLanguage = Localizer::getInstance()->getContentLanguage();

			$propertySpecificationLookup = new PropertySpecificationLookup(
				$containerBuilder->singleton( 'Store', null ),
				$containerBuilder->singleton( 'EntityCache' )
			);

			$propertySpecificationLookup->setLanguageCode(
				$contentLanguage->getCode()
			);

			return $propertySpecificationLookup;
		} );

		/**
		 * @var ProtectionValidator
		 */
		$containerBuilder->registerCallback( 'ProtectionValidator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ProtectionValidator', '\SMW\Protection\ProtectionValidator' );

			$settings = $containerBuilder->singleton( 'Settings' );

			$protectionValidator = new ProtectionValidator(
				$containerBuilder->singleton( 'Store', null ),
				$containerBuilder->singleton( 'EntityCache' ),
				$containerBuilder->singleton( 'PermissionManager' )
			);

			$protectionValidator->setImportPerformers(
				$settings->get( 'smwgImportPerformers' )
			);

			$protectionValidator->setEditProtectionRight(
				$settings->get( 'smwgEditProtectionRight' )
			);

			$protectionValidator->setCreateProtectionRight(
				$settings->get( 'smwgCreateProtectionRight' )
			);

			$protectionValidator->setChangePropagationProtection(
				$settings->get( 'smwgChangePropagationProtection' )
			);

			return $protectionValidator;
		} );

		/**
		 * @var TitlePermissions
		 */
		$containerBuilder->registerCallback( 'TitlePermissions', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'TitlePermissions', '\SMW\MediaWiki\Permission\TitlePermissions' );

			$titlePermissions = new TitlePermissions(
				$containerBuilder->create( 'ProtectionValidator' ),
				$containerBuilder->singleton( 'PermissionManager' )
			);

			return $titlePermissions;
		} );

		/**
		 * @var EditProtectionUpdater
		 */
		$containerBuilder->registerCallback( 'EditProtectionUpdater', function( $containerBuilder, \WikiPage $wikiPage, \User $user = null ) {
			$containerBuilder->registerExpectedReturnType( 'EditProtectionUpdater', '\SMW\Protection\EditProtectionUpdater' );

			$editProtectionUpdater = new EditProtectionUpdater(
				$wikiPage,
				$user
			);

			$editProtectionUpdater->setEditProtectionRight(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgEditProtectionRight' )
			);

			$editProtectionUpdater->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			return $editProtectionUpdater;
		} );

		/**
		 * @var PropertyRestrictionExaminer
		 */
		$containerBuilder->registerCallback( 'PropertyRestrictionExaminer', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertyRestrictionExaminer', '\SMW\PropertyRestrictionExaminer' );

			$propertyRestrictionExaminer = new PropertyRestrictionExaminer();

			$propertyRestrictionExaminer->setCreateProtectionRight(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgCreateProtectionRight' )
			);

			return $propertyRestrictionExaminer;
		} );

		/**
		 * @var HierarchyLookup
		 */
		$containerBuilder->registerCallback( 'HierarchyLookup', function( $containerBuilder, $store = null, $cacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'HierarchyLookup', '\SMW\HierarchyLookup' );

			$hierarchyLookup = new HierarchyLookup(
				$containerBuilder->singleton( 'Store', null ),
				$containerBuilder->singleton( 'Cache', $cacheType )
			);

			$hierarchyLookup->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			$hierarchyLookup->setSubcategoryDepth(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgQSubcategoryDepth' )
			);

			$hierarchyLookup->setSubpropertyDepth(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgQSubpropertyDepth' )
			);

			return $hierarchyLookup;
		} );

		/**
		 * @var PropertyLabelFinder
		 */
		$containerBuilder->registerCallback( 'PropertyLabelFinder', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertyLabelFinder', '\SMW\PropertyLabelFinder' );

			$lang = Localizer::getInstance()->getLang();

			$propertyLabelFinder = new PropertyLabelFinder(
				$containerBuilder->singleton( 'Store', null ),
				$lang->getPropertyLabels(),
				$lang->getCanonicalPropertyLabels(),
				$lang->getCanonicalDatatypeLabels()
			);

			return $propertyLabelFinder;
		} );

		/**
		 * @var DisplayTitleFinder
		 */
		$containerBuilder->registerCallback( 'DisplayTitleFinder', function( $containerBuilder, $store = null ) {
			$containerBuilder->registerExpectedReturnType( 'DisplayTitleFinder', '\SMW\DisplayTitleFinder' );

			$store = $store === null ? $containerBuilder->singleton( 'Store', null ) : $store;
			$settings = $containerBuilder->singleton( 'Settings' );

			$displayTitleFinder = new DisplayTitleFinder(
				$store,
				$containerBuilder->singleton( 'EntityCache' )
			);

			$displayTitleFinder->setCanUse(
				$settings->isFlagSet( 'smwgDVFeatures', SMW_DV_WPV_DTITLE )
			);

			return $displayTitleFinder;
		} );

		/**
		 * @var MagicWordsFinder
		 */
		$containerBuilder->registerCallback( 'MagicWordsFinder', function( $containerBuilder, $parserOutput = null ) {
			$containerBuilder->registerExpectedReturnType( 'MagicWordsFinder', '\SMW\MediaWiki\MagicWordsFinder' );

			$magicWordsFinder = new \SMW\MediaWiki\MagicWordsFinder(
				$parserOutput,
				$containerBuilder->singleton( 'MagicWordFactory' )
			);

			return $magicWordsFinder;
		} );

		/**
		 * @var DependencyValidator
		 */
		$containerBuilder->registerCallback( 'DependencyValidator', function( $containerBuilder, $store = null ) {
			$containerBuilder->registerExpectedReturnType( 'DependencyValidator', '\SMW\DependencyValidator' );

			$store = $store === null ? $containerBuilder->singleton( 'Store', null ) : $store;
			$settings = $containerBuilder->singleton( 'Settings' );

			$queryDependencyLinksStoreFactory = $containerBuilder->singleton( 'QueryDependencyLinksStoreFactory' );

			$dependencyValidator = new DependencyValidator(
				$containerBuilder->create( 'NamespaceExaminer' ),
				$queryDependencyLinksStoreFactory->newDependencyLinksValidator(),
				$containerBuilder->singleton( 'EntityCache' )
			);

			return $dependencyValidator;
		} );

		/**
		 * @var PreferenceExaminer
		 */
		$containerBuilder->registerCallback( 'PreferenceExaminer', function( $containerBuilder, \User $user ) {
			$containerBuilder->registerExpectedReturnType( 'PreferenceExaminer', '\SMW\MediaWiki\Preference\PreferenceExaminer' );

			$preferenceExaminer = new PreferenceExaminer(
				$user
			);

			return $preferenceExaminer;
		} );

	}

}
