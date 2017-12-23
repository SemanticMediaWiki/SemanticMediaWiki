<?php

namespace SMW\Services;

use Onoi\BlobStore\BlobStore;
use Onoi\CallbackContainer\CallbackContainer;
use Onoi\CallbackContainer\ContainerBuilder;
use SMW\Factbox\FactboxFactory;
use SMW\MediaWiki\Jobs\JobFactory;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\Database;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\TitleCreator;
use SMW\MediaWiki\JobQueue;
use SMW\Query\QuerySourceFactory;
use SMW\SQLStore\ChangeOp\TempChangeOpStore;
use SMW\Query\Result\CachedQueryResultPrefetcher;
use SMW\Utils\BufferedStatsdCollector;
use SMW\Parser\LinksProcessor;
use SMW\PropertyRestrictionExaminer;
use SMW\Protection\ProtectionValidator;
use SMW\Protection\EditProtectionUpdater;
use SMW\Services\DataValueServiceFactory;
use SMW\Settings;
use SMW\StoreFactory;
use SMW\MessageFormatter;
use SMW\NamespaceExaminer;
use SMW\ParserData;
use SMW\ContentParser;
use SMW\Updater\DeferredCallableUpdate;
use SMW\Updater\DeferredTransactionalUpdate;
use SMW\InMemoryPoolCache;
use SMW\PropertyAnnotatorFactory;
use SMW\CacheFactory;
use SMW\IteratorFactory;
use SMW\QueryFactory;
use SMW\DataItemFactory;
use SMW\PropertySpecificationLookup;
use SMW\HierarchyLookup;
use SMW\PropertyLabelFinder;
use SMW\CachedPropertyValuesPrefetcher;
use SMW\Localizer;
use SMW\MediaWiki\DBConnectionProvider;
use SMW\Utils\TempFile;
use SMW\PostProcHandler;
use SMW\Utils\JsonSchemaValidator;
use JsonSchema\Validator as SchemaValidator;

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
		$this->registerCallbackHandlers( $containerBuilder );
		$this->registerCallbackHandlersByFactory( $containerBuilder );
		$this->registerCallbackHandlersByConstructedInstance( $containerBuilder );
	}

	private function registerCallbackHandlers( $containerBuilder ) {

		$containerBuilder->registerCallback( 'Settings', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'Settings', '\SMW\Settings' );
			return Settings::newFromGlobals();
		} );

		$containerBuilder->registerCallback( 'Store', function( $containerBuilder, $storeClass = null ) {
			$containerBuilder->registerExpectedReturnType( 'Store', '\SMW\Store' );

			$settings = $containerBuilder->singleton( 'Settings' );
			$storeClass = $storeClass !== null ? $storeClass : $settings->get( 'smwgDefaultStore' );

			$store = StoreFactory::getStore( $storeClass );

			$options = $store->getOptions();
			$options->set( 'smwgDefaultStore', $settings->get( 'smwgDefaultStore' ) );
			$options->set( 'smwgSemanticsEnabled', $settings->get( 'smwgSemanticsEnabled' ) );
			$options->set( 'smwgAutoRefreshSubject', $settings->get( 'smwgAutoRefreshSubject' ) );
			$options->set( 'smwgEnableUpdateJobs', $settings->get( 'smwgEnableUpdateJobs' ) );

			return $store;
		} );

		$containerBuilder->registerCallback( 'Cache', function( $containerBuilder, $cacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'Cache', '\Onoi\Cache\Cache' );
			return $containerBuilder->create( 'CacheFactory' )->newMediaWikiCompositeCache( $cacheType );
		} );

		$containerBuilder->registerCallback( 'NamespaceExaminer', function() use ( $containerBuilder ) {
			return NamespaceExaminer::newFromArray( $containerBuilder->singleton( 'Settings' )->get( 'smwgNamespacesWithSemanticLinks' ) );
		} );

		$containerBuilder->registerCallback( 'ParserData', function( $containerBuilder, \Title $title, \ParserOutput $parserOutput ) {
			$containerBuilder->registerExpectedReturnType( 'ParserData', '\SMW\ParserData' );
			return new ParserData( $title, $parserOutput );
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

		$containerBuilder->registerCallback( 'PageUpdater', function( $containerBuilder, $connection, DeferredTransactionalUpdate $deferredTransactionalUpdate = null ) {
			$containerBuilder->registerExpectedReturnType( 'PageUpdater', '\SMW\MediaWiki\PageUpdater' );
			return new PageUpdater( $connection, $deferredTransactionalUpdate );
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

		$containerBuilder->registerCallback( 'TitleCreator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'TitleCreator', '\SMW\MediaWiki\TitleCreator' );
			return new TitleCreator();
		} );

		$containerBuilder->registerCallback( 'ContentParser', function( $containerBuilder, \Title $title ) {
			$containerBuilder->registerExpectedReturnType( 'ContentParser', '\SMW\ContentParser' );
			return new ContentParser( $title );
		} );

		$containerBuilder->registerCallback( 'DeferredCallableUpdate', function( $containerBuilder, \Closure $callback = null ) {
			$containerBuilder->registerExpectedReturnType( 'DeferredCallableUpdate', '\SMW\Updater\DeferredCallableUpdate' );
			return new DeferredCallableUpdate( $callback );
		} );

		$containerBuilder->registerCallback( 'DeferredTransactionalUpdate', function( $containerBuilder, \Closure $callback = null, Database $connection = null ) {
			$containerBuilder->registerExpectedReturnType( 'DeferredTransactionalUpdate', '\SMW\Updater\DeferredTransactionalUpdate' );
			return new DeferredTransactionalUpdate( $callback, $connection );
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
		 * @var DBConnectionProvider
		 */
		$containerBuilder->registerCallback( 'DBConnectionProvider', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'DBConnectionProvider', '\SMW\MediaWiki\DBConnectionProvider' );
			return new DBConnectionProvider();
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

			$postProcHandler = new PostProcHandler(
				$parserOutput,
				$containerBuilder->singleton( 'Cache' )
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
	}

	private function registerCallbackHandlersByFactory( $containerBuilder ) {

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
			$containerBuilder->registerExpectedReturnType( 'JobFactory', '\SMW\MediaWiki\Jobs\JobFactory' );
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
				$containerBuilder->create( 'Store' ),
				$containerBuilder->create( 'Settings' )->get( 'smwgQuerySources' ),
				$containerBuilder->create( 'Settings' )->get( 'smwgSparqlQueryEndpoint' )
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
				$containerBuilder->create( 'Store' ),
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
				$containerBuilder->create( 'Store' ),
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
		$containerBuilder->registerCallback( 'HierarchyLookup', function( $containerBuilder, $cacheType = null ) {
			$containerBuilder->registerExpectedReturnType( 'HierarchyLookup', '\SMW\HierarchyLookup' );

			$hierarchyLookup = new HierarchyLookup(
				$containerBuilder->create( 'Store' ),
				$containerBuilder->singleton( 'Cache', $cacheType )
			);

			$hierarchyLookup->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			$hierarchyLookup->setSubcategoryDepth(
				$containerBuilder->create( 'Settings' )->get( 'smwgQSubcategoryDepth' )
			);

			$hierarchyLookup->setSubpropertyDepth(
				$containerBuilder->create( 'Settings' )->get( 'smwgQSubpropertyDepth' )
			);

			return $hierarchyLookup;
		} );

		/**
		 * @var PropertyLabelFinder
		 */
		$containerBuilder->registerCallback( 'PropertyLabelFinder', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertyLabelFinder', '\SMW\PropertyLabelFinder' );

			$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage();

			$propertyLabelFinder = new PropertyLabelFinder(
				$containerBuilder->create( 'Store' ),
				$extraneousLanguage->getPropertyLabels(),
				$extraneousLanguage->getCanonicalPropertyLabels(),
				$extraneousLanguage->getCanonicalDatatypeLabels()
			);

			return $propertyLabelFinder;
		} );

		/**
		 * @var TempChangeOpStore
		 */
		$containerBuilder->registerCallback( 'TempChangeOpStore', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'TempChangeOpStore', '\SMW\SQLStore\ChangeOp\TempChangeOpStore' );

			$cacheFactory = $containerBuilder->create( 'CacheFactory' );
			$cacheType = null;

			$tempChangeOpStore = new TempChangeOpStore(
				$cacheFactory->newMediaWikiCompositeCache( $cacheType )
			);

			$tempChangeOpStore->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			return $tempChangeOpStore;
		} );
	}

}
