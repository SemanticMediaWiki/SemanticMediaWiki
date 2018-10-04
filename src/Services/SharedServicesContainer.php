<?php

namespace SMW\Services;

use JsonSchema\Validator as SchemaValidator;
use Onoi\BlobStore\BlobStore;
use Onoi\CallbackContainer\CallbackContainer;
use Onoi\CallbackContainer\ContainerBuilder;
use SMW\CachedPropertyValuesPrefetcher;
use SMW\CacheFactory;
use SMW\ContentParser;
use SMW\DataItemFactory;
use SMW\Factbox\FactboxFactory;
use SMW\HierarchyLookup;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Localizer;
use SMW\MediaWiki\Database;
use SMW\Connection\ConnectionManager;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\TitleFactory;
use SMW\MessageFormatter;
use SMW\NamespaceExaminer;
use SMW\Parser\LinksProcessor;
use SMW\PermissionPthValidator;
use SMW\ParserData;
use SMW\PostProcHandler;
use SMW\PropertyAnnotatorFactory;
use SMW\PropertyLabelFinder;
use SMW\PropertyRestrictionExaminer;
use SMW\PropertySpecificationLookup;
use SMW\Protection\EditProtectionUpdater;
use SMW\Protection\ProtectionValidator;
use SMW\Query\QuerySourceFactory;
use SMW\Query\Result\CachedQueryResultPrefetcher;
use SMW\Schema\SchemaFactory;
use SMW\Settings;
use SMW\Options;
use SMW\StoreFactory;
use SMW\Utils\BufferedStatsdCollector;
use SMW\Utils\JsonSchemaValidator;
use SMW\Utils\TempFile;
use SMW\Elastic\ElasticFactory;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\QueryFactory;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\Processor\ParamListProcessor;

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

		$this->registerCallbackHandlers( $containerBuilder );
		$this->registerCallableFactories( $containerBuilder );
		$this->registerCallbackHandlersByConstructedInstance( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @return Store
	 */
	public function newStore( $containerBuilder, $storeClass = null ) {

		$containerBuilder->registerExpectedReturnType( 'Store', '\SMW\Store' );
		$settings = $containerBuilder->singleton( 'Settings' );

		if ( $storeClass === null || $storeClass === '' ) {
			$storeClass = $settings->get( 'smwgDefaultStore' );
		}

		$store = StoreFactory::getStore( $storeClass );

		$configs = [
			'smwgDefaultStore',
			'smwgSemanticsEnabled',
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

	private function registerCallbackHandlers( $containerBuilder ) {

		$containerBuilder->registerCallback( 'Settings', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'Settings', '\SMW\Settings' );
			return Settings::newFromGlobals();
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

		$containerBuilder->registerCallback( 'Cache', function( $containerBuilder, $cacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'Cache', '\Onoi\Cache\Cache' );
			return $containerBuilder->create( 'CacheFactory' )->newMediaWikiCompositeCache( $cacheType );
		} );

		$containerBuilder->registerCallback( 'NamespaceExaminer', function() use ( $containerBuilder ) {
			return NamespaceExaminer::newFromArray( $containerBuilder->singleton( 'Settings' )->get( 'smwgNamespacesWithSemanticLinks' ) );
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
			return new MediaWikiNsContentReader();
		} );

		$containerBuilder->registerCallback( 'PageCreator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PageCreator', '\SMW\MediaWiki\PageCreator' );
			return new PageCreator();
		} );

		$containerBuilder->registerCallback( 'PageUpdater', function( $containerBuilder, $connection, TransactionalCallableUpdate $transactionalCallableUpdate = null ) {
			$containerBuilder->registerExpectedReturnType( 'PageUpdater', '\SMW\MediaWiki\PageUpdater' );
			return new PageUpdater( $connection, $transactionalCallableUpdate );
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

		$containerBuilder->registerCallback( 'ContentParser', function( $containerBuilder, \Title $title ) {
			$containerBuilder->registerExpectedReturnType( 'ContentParser', '\SMW\ContentParser' );
			return new ContentParser( $title );
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
		 * @var PropertyAnnotatorFactory
		 */
		$containerBuilder->registerCallback( 'PropertyAnnotatorFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertyAnnotatorFactory', '\SMW\PropertyAnnotatorFactory' );
			return new PropertyAnnotatorFactory();
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
		 * @var ElasticFactory
		 */
		$containerBuilder->registerCallback( 'ElasticFactory', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ElasticFactory', ElasticFactory::class );
			return new ElasticFactory();
		} );

		/**
		 * @var Creator
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

	private function registerCallableFactories( $containerBuilder ) {

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

	private function registerCallbackHandlersByConstructedInstance( $containerBuilder ) {

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
		 * @var CachedQueryResultPrefetcher
		 */
		$containerBuilder->registerCallback( 'CachedQueryResultPrefetcher', function( $containerBuilder, $cacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'CachedQueryResultPrefetcher', '\SMW\Query\Result\CachedQueryResultPrefetcher' );

			$settings = $containerBuilder->singleton( 'Settings' );
			$cacheType = $cacheType === null ? $settings->get( 'smwgQueryResultCacheType' ) : $cacheType;

			$cachedQueryResultPrefetcher = new CachedQueryResultPrefetcher(
				$containerBuilder->singleton( 'Store', null ),
				$containerBuilder->singleton( 'QueryFactory' ),
				$containerBuilder->create(
					'BlobStore',
					CachedQueryResultPrefetcher::CACHE_NAMESPACE,
					$cacheType,
					$settings->get( 'smwgQueryResultCacheLifetime' )
				),
				$containerBuilder->singleton(
					'BufferedStatsdCollector',
					CachedQueryResultPrefetcher::STATSD_ID
				)
			);

			$cachedQueryResultPrefetcher->setDependantHashIdExtension(
				// If the mix of dataTypes changes then modify the hash
				$settings->get( 'smwgFulltextSearchIndexableDataTypes' ) .

				// If the collation is altered then modify the hash as it
				// is likely that the sort order of results change
				$settings->get( 'smwgEntityCollation' ) .

				// Changing the sobj has computation should invalidate
				// existing caches to avoid oudated references SOBJ IDs
				$settings->get( 'smwgUseComparableContentHash' )
			);

			$cachedQueryResultPrefetcher->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			$cachedQueryResultPrefetcher->setNonEmbeddedCacheLifetime(
				$settings->get( 'smwgQueryResultNonEmbeddedCacheLifetime' )
			);

			return $cachedQueryResultPrefetcher;
		} );

		/**
		 * @var CachedPropertyValuesPrefetcher
		 */
		$containerBuilder->registerCallback( 'CachedPropertyValuesPrefetcher', function( $containerBuilder, $cacheType = null, $ttl = 604800 ) {
			$containerBuilder->registerExpectedReturnType( 'CachedPropertyValuesPrefetcher', CachedPropertyValuesPrefetcher::class );

			$cachedPropertyValuesPrefetcher = new CachedPropertyValuesPrefetcher(
				$containerBuilder->singleton( 'Store', null ),
				$containerBuilder->create( 'BlobStore', CachedPropertyValuesPrefetcher::CACHE_NAMESPACE, $cacheType, $ttl )
			);

			return $cachedPropertyValuesPrefetcher;
		} );

		/**
		 * @var BufferedStatsdCollector
		 */
		$containerBuilder->registerCallback( 'BufferedStatsdCollector', function( $containerBuilder, $id ) {
			$containerBuilder->registerExpectedReturnType( 'BufferedStatsdCollector', '\SMW\Utils\BufferedStatsdCollector' );

			// Explicitly use the DB to access a SqlBagOstuff instance
			$cacheType = CACHE_DB;
			$ttl = 0;

			$bufferedStatsdCollector = new BufferedStatsdCollector(
				$containerBuilder->create( 'BlobStore', BufferedStatsdCollector::CACHE_NAMESPACE, $cacheType, $ttl ),
				$id
			);

			return $bufferedStatsdCollector;
		} );

		/**
		 * @var PropertySpecificationLookup
		 */
		$containerBuilder->registerCallback( 'PropertySpecificationLookup', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertySpecificationLookup', '\SMW\PropertySpecificationLookup' );

			$propertySpecificationLookup = new PropertySpecificationLookup(
				$containerBuilder->singleton( 'CachedPropertyValuesPrefetcher' ),
				$containerBuilder->singleton( 'InMemoryPoolCache' )->getPoolCacheById( PropertySpecificationLookup::POOLCACHE_ID )
			);

			return $propertySpecificationLookup;
		} );

		/**
		 * @var ProtectionValidator
		 */
		$containerBuilder->registerCallback( 'ProtectionValidator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'ProtectionValidator', '\SMW\Protection\ProtectionValidator' );

			$protectionValidator = new ProtectionValidator(
				$containerBuilder->singleton( 'CachedPropertyValuesPrefetcher' ),
				$containerBuilder->singleton( 'InMemoryPoolCache' )->getPoolCacheById( ProtectionValidator::POOLCACHE_ID )
			);

			$protectionValidator->setEditProtectionRight(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgEditProtectionRight' )
			);

			$protectionValidator->setCreateProtectionRight(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgCreateProtectionRight' )
			);

			$protectionValidator->setChangePropagationProtection(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgChangePropagationProtection' )
			);

			return $protectionValidator;
		} );

		/**
		 * @var PermissionPthValidator
		 */
		$containerBuilder->registerCallback( 'PermissionPthValidator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PermissionPthValidator', '\SMW\PermissionPthValidator' );

			$permissionPthValidator = new PermissionPthValidator(
				$containerBuilder->create( 'ProtectionValidator' )
			);

			return $permissionPthValidator;
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
	}

}
