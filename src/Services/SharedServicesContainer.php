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
use SMW\MediaWiki\JobQueueLookup;
use SMW\Query\QuerySourceFactory;
use SMW\SQLStore\ChangeOp\TempChangeOpStore;
use SMW\Query\Result\CachedQueryResultPrefetcher;
use SMW\Utils\BufferedStatsdCollector;
use SMW\Parser\LinksProcessor;
use SMW\Protection\EditProtectionValidator;
use SMW\Protection\EditProtectionUpdater;
use SMW\Services\DataValueServiceFactory;
use SMW\Settings;
use SMW\StoreFactory;
use SMW\MessageFormatter;
use SMW\NamespaceExaminer;
use SMW\ParserData;
use SMW\ContentParser;
use SMW\Updater\DeferredCallableUpdate;
use SMW\Updater\TransactionalDeferredCallableUpdate;
use SMW\InMemoryPoolCache;
use SMW\PropertyAnnotatorFactory;
use SMW\CacheFactory;
use SMW\IteratorFactory;
use SMW\QueryFactory;
use SMW\DataItemFactory;
use SMW\PropertySpecificationLookup;
use SMW\PropertyHierarchyLookup;
use SMW\PropertyLabelFinder;
use SMW\CachedPropertyValuesPrefetcher;
use SMW\Localizer;
use SMW\MediaWiki\DatabaseConnectionProvider;

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

		$containerBuilder->registerCallback( 'PageUpdater', function( $containerBuilder, $connection, TransactionalDeferredCallableUpdate $transactionalDeferredCallableUpdate = null ) {
			$containerBuilder->registerExpectedReturnType( 'PageUpdater', '\SMW\MediaWiki\PageUpdater' );
			return new PageUpdater( $connection, $transactionalDeferredCallableUpdate );
		} );

		$containerBuilder->registerCallback( 'JobQueueLookup', function( $containerBuilder, Database $connection ) {
			$containerBuilder->registerExpectedReturnType( 'JobQueueLookup', '\SMW\MediaWiki\JobQueueLookup' );
			return new JobQueueLookup( $connection );
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

		$containerBuilder->registerCallback( 'TransactionalDeferredCallableUpdate', function( $containerBuilder, \Closure $callback = null, Database $connection = null ) {
			$containerBuilder->registerExpectedReturnType( 'TransactionalDeferredCallableUpdate', '\SMW\Updater\TransactionalDeferredCallableUpdate' );
			return new TransactionalDeferredCallableUpdate( $callback, $connection );
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
		 * @var DatabaseConnectionProvider
		 */
		$containerBuilder->registerCallback( 'DatabaseConnectionProvider', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'DatabaseConnectionProvider', '\SMW\MediaWiki\DatabaseConnectionProvider' );
			return new DatabaseConnectionProvider();
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

			$cachedQueryResultPrefetcher->setHashModifier(
				$settings->get( 'smwgFulltextSearchIndexableDataTypes' )
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
		 * @var EditProtectionValidator
		 */
		$containerBuilder->registerCallback( 'EditProtectionValidator', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'EditProtectionValidator', '\SMW\Protection\EditProtectionValidator' );

			$editProtectionValidator = new EditProtectionValidator(
				$containerBuilder->singleton( 'CachedPropertyValuesPrefetcher' ),
				$containerBuilder->singleton( 'InMemoryPoolCache' )->getPoolCacheById( EditProtectionValidator::POOLCACHE_ID )
			);

			$editProtectionValidator->setEditProtectionRight(
				$containerBuilder->singleton( 'Settings' )->get( 'smwgEditProtectionRight' )
			);

			return $editProtectionValidator;
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
		 * @var PropertyHierarchyLookup
		 */
		$containerBuilder->registerCallback( 'PropertyHierarchyLookup', function( $containerBuilder ) {
			$containerBuilder->registerExpectedReturnType( 'PropertyHierarchyLookup', '\SMW\PropertyHierarchyLookup' );

			$propertyHierarchyLookup = new PropertyHierarchyLookup(
				$containerBuilder->create( 'Store' ),
				$containerBuilder->singleton( 'InMemoryPoolCache' )->getPoolCacheById( PropertyHierarchyLookup::POOLCACHE_ID )
			);

			$propertyHierarchyLookup->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			$propertyHierarchyLookup->setSubcategoryDepth(
				$containerBuilder->create( 'Settings' )->get( 'smwgQSubcategoryDepth' )
			);

			$propertyHierarchyLookup->setSubpropertyDepth(
				$containerBuilder->create( 'Settings' )->get( 'smwgQSubpropertyDepth' )
			);

			return $propertyHierarchyLookup;
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
				$cacheFactory->newMediaWikiCompositeCache( $cacheType ),
				$cacheFactory->getCachePrefix()
			);

			$tempChangeOpStore->setLogger(
				$containerBuilder->singleton( 'MediaWikiLogger' )
			);

			return $tempChangeOpStore;
		} );
	}

}
