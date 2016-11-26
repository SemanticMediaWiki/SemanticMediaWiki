<?php

namespace SMW;

use LBFactory;
use MediaWiki\MediaWikiServices;
use Onoi\BlobStore\BlobStore;
use Onoi\CallbackContainer\CallbackContainer;
use Onoi\CallbackContainer\CallbackLoader;
use SMW\Factbox\FactboxFactory;
use SMW\MediaWiki\Jobs\JobFactory;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\Database;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\TitleCreator;
use SMW\MediaWiki\JobQueueLookup;
use SMW\Query\QuerySourceFactory;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\NullLogger;
use SMW\SQLStore\TransitionalDiffStore;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SharedCallbackContainer implements CallbackContainer {

	/**
	 * @see CallbackContainer::register
	 *
	 * @since 2.3
	 */
	public function register( CallbackLoader $callbackLoader ) {
		$this->registerCallbackHandlers( $callbackLoader );
		$this->registerCallbackHandlersByMediaWikiServcies( $callbackLoader );
		$this->registerCallbackHandlersByFactory( $callbackLoader );
		$this->registerCallbackHandlersByConstructedInstance( $callbackLoader );
	}

	private function registerCallbackHandlers( $callbackLoader ) {

		$callbackLoader->registerExpectedReturnType( 'Settings', '\SMW\Settings' );

		$callbackLoader->registerCallback( 'Settings', function() use ( $callbackLoader )  {
			return Settings::newFromGlobals();
		} );

		$callbackLoader->registerExpectedReturnType( 'Store', '\SMW\Store' );

		$callbackLoader->registerCallback( 'Store', function( $store = null ) use ( $callbackLoader ) {
			return StoreFactory::getStore( $store !== null ? $store : $callbackLoader->singleton( 'Settings' )->get( 'smwgDefaultStore' ) );
		} );

		$callbackLoader->registerExpectedReturnType( 'Cache', '\Onoi\Cache\Cache' );

		$callbackLoader->registerCallback( 'Cache', function( $cacheType = null ) use ( $callbackLoader ) {
			return $callbackLoader->load( 'CacheFactory' )->newMediaWikiCompositeCache( $cacheType );
		} );

		$callbackLoader->registerCallback( 'NamespaceExaminer', function() use ( $callbackLoader ) {
			return NamespaceExaminer::newFromArray( $callbackLoader->singleton( 'Settings' )->get( 'smwgNamespacesWithSemanticLinks' ) );
		} );

		$callbackLoader->registerExpectedReturnType( 'ParserData', '\SMW\ParserData' );

		$callbackLoader->registerCallback( 'ParserData', function( \Title $title, \ParserOutput $parserOutput ) {
			return new ParserData( $title, $parserOutput );
		} );

		$callbackLoader->registerCallback( 'MessageFormatter', function( \Language $language ) {
			return new MessageFormatter( $language );
		} );

		$callbackLoader->registerCallback( 'MediaWikiNsContentReader', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'MediaWikiNsContentReader', '\SMW\MediaWiki\MediaWikiNsContentReader' );
			return new MediaWikiNsContentReader();
		} );

		$callbackLoader->registerExpectedReturnType( 'PageCreator', '\SMW\MediaWiki\PageCreator' );

		$callbackLoader->registerCallback( 'PageCreator', function() {
			return new PageCreator();
		} );

		$callbackLoader->registerCallback( 'JobQueueLookup', function( Database $connection ) use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'JobQueueLookup', '\SMW\MediaWiki\JobQueueLookup' );
			return new JobQueueLookup( $connection );
		} );

		$callbackLoader->registerCallback( 'ManualEntryLogger', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'ManualEntryLogger', '\SMW\MediaWiki\ManualEntryLogger' );
			return new ManualEntryLogger();
		} );

		$callbackLoader->registerExpectedReturnType( 'TitleCreator', '\SMW\MediaWiki\TitleCreator' );

		$callbackLoader->registerCallback( 'TitleCreator', function() {
			return new TitleCreator();
		} );

		$callbackLoader->registerExpectedReturnType( 'ContentParser', '\SMW\ContentParser' );

		$callbackLoader->registerCallback( 'ContentParser', function( \Title $title ) {
			return new ContentParser( $title );
		} );

		$callbackLoader->registerExpectedReturnType( 'DeferredCallableUpdate', '\SMW\DeferredCallableUpdate' );

		$callbackLoader->registerCallback( 'DeferredCallableUpdate', function( \Closure $callback ) {
			return new DeferredCallableUpdate( $callback );
		} );

		/**
		 * @var InMemoryPoolCache
		 */
		$callbackLoader->registerCallback( 'InMemoryPoolCache', function() use( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'InMemoryPoolCache', '\SMW\InMemoryPoolCache' );
			return InMemoryPoolCache::getInstance();
		} );

		/**
		 * @var PropertyAnnotatorFactory
		 */
		$callbackLoader->registerCallback( 'PropertyAnnotatorFactory', function() use( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'PropertyAnnotatorFactory', '\SMW\PropertyAnnotatorFactory' );
			return new PropertyAnnotatorFactory();
		} );
	}

	private function registerCallbackHandlersByMediaWikiServcies( $callbackLoader ) {

		$callbackLoader->registerExpectedReturnType( 'WikiPage', '\WikiPage' );

		$callbackLoader->registerCallback( 'WikiPage', function( \Title $title ) {
			return \WikiPage::factory( $title );
		} );

		$callbackLoader->registerExpectedReturnType( 'DBLoadBalancer', '\LoadBalancer' );

		$callbackLoader->registerCallback( 'DBLoadBalancer', function() {
			if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getDBLoadBalancer' ) ) { // > MW 1.27
				return MediaWikiServices::getInstance()->getDBLoadBalancer();
			}

			return LBFactory::singleton()->getMainLB();
		} );

		$callbackLoader->registerCallback( 'DefaultSearchEngineTypeForDB', function( \IDatabase $db ) use ( $callbackLoader ) {
			if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( 'SearchEngineFactory', 'getSearchEngineClass' ) ) { // MW > 1.27
				return MediaWikiServices::getInstance()->getSearchEngineFactory()->getSearchEngineClass( $db );
			}

			return $db->getSearchEngine();
		} );

		$callbackLoader->registerCallback( 'MediaWikiLogger', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'MediaWikiLogger', '\Psr\Log\LoggerInterface' );
			if ( class_exists( '\MediaWiki\Logger\LoggerFactory' ) ) {
				return LoggerFactory::getInstance( 'smw' );
			}

			return new NullLogger();
		} );
	}

	private function registerCallbackHandlersByFactory( $callbackLoader ) {

		/**
		 * @var CacheFactory
		 */
		$callbackLoader->registerExpectedReturnType( 'CacheFactory', '\SMW\CacheFactory' );

		$callbackLoader->registerCallback( 'CacheFactory', function( $mainCacheType = null ) {
			return new CacheFactory( $mainCacheType );
		} );

		/**
		 * @var IteratorFactory
		 */
		$callbackLoader->registerExpectedReturnType( 'IteratorFactory', '\SMW\IteratorFactory' );

		$callbackLoader->registerCallback( 'IteratorFactory', function() {
			return new IteratorFactory();
		} );

		/**
		 * @var JobFactory
		 */
		$callbackLoader->registerExpectedReturnType( 'JobFactory', '\SMW\MediaWiki\Jobs\JobFactory' );

		$callbackLoader->registerCallback( 'JobFactory', function() {
			return new JobFactory();
		} );

		/**
		 * @var FactboxFactory
		 */
		$callbackLoader->registerExpectedReturnType( 'FactboxFactory', '\SMW\Factbox\FactboxFactory' );

		$callbackLoader->registerCallback( 'FactboxFactory', function() {
			return new FactboxFactory();
		} );

		/**
		 * @var QuerySourceFactory
		 */
		$callbackLoader->registerCallback( 'QuerySourceFactory', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'QuerySourceFactory', '\SMW\Query\QuerySourceFactory' );

			return new QuerySourceFactory(
				$callbackLoader->load( 'Store' ),
				$callbackLoader->load( 'Settings' )->get( 'smwgQuerySources' )
			);
		} );

		/**
		 * @var QueryFactory
		 */
		$callbackLoader->registerCallback( 'QueryFactory', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'QueryFactory', '\SMW\QueryFactory' );
			return new QueryFactory();
		} );

		/**
		 * @var DataItemFactory
		 */
		$callbackLoader->registerCallback( 'DataItemFactory', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'DataItemFactory', '\SMW\DataItemFactory' );
			return new DataItemFactory();
		} );
	}

	private function registerCallbackHandlersByConstructedInstance( $callbackLoader ) {

		/**
		 * @var BlobStore
		 */
		$callbackLoader->registerCallback( 'BlobStore', function( $namespace, $cacheType = null, $ttl = 0 ) use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'BlobStore', '\Onoi\BlobStore\BlobStore' );

			$cacheFactory = $callbackLoader->load( 'CacheFactory' );

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
		$callbackLoader->registerCallback( 'CachedQueryResultPrefetcher', function( $cacheType = null ) use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'CachedQueryResultPrefetcher', '\SMW\CachedQueryResultPrefetcher' );

			$settings = $callbackLoader->load( 'Settings' );
			$cacheType = $cacheType === null ? $settings->get( 'smwgQueryResultCacheType' ) : $cacheType;

			$cachedQueryResultPrefetcher = new CachedQueryResultPrefetcher(
				$callbackLoader->load( 'Store' ),
				$callbackLoader->singleton( 'QueryFactory' ),
				$callbackLoader->create(
					'BlobStore',
					CachedQueryResultPrefetcher::CACHE_NAMESPACE, $cacheType,
					$settings->get( 'smwgQueryResultCacheLifetime' )
				),
				$callbackLoader->create(
					'TransientStatsdCollector',
					CachedQueryResultPrefetcher::STATSD_ID
				)
			);

			$cachedQueryResultPrefetcher->setLogger(
				$callbackLoader->singleton( 'MediaWikiLogger' )
			);

			$cachedQueryResultPrefetcher->setNonEmbeddedCacheLifetime(
				$settings->get( 'smwgQueryResultNonEmbeddedCacheLifetime' )
			);

			return $cachedQueryResultPrefetcher;
		} );

		/**
		 * @var CachedPropertyValuesPrefetcher
		 */
		$callbackLoader->registerCallback( 'CachedPropertyValuesPrefetcher', function( $cacheType = null, $ttl = 604800 ) use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'CachedPropertyValuesPrefetcher', '\SMW\CachedPropertyValuesPrefetcher' );

			$cachedPropertyValuesPrefetcher = new CachedPropertyValuesPrefetcher(
				$callbackLoader->load( 'Store' ),
				$callbackLoader->load( 'BlobStore', CachedPropertyValuesPrefetcher::CACHE_NAMESPACE, $cacheType, $ttl )
			);

			return $cachedPropertyValuesPrefetcher;
		} );

		/**
		 * @var TransientStatsdCollector
		 */
		$callbackLoader->registerCallback( 'TransientStatsdCollector', function( $id ) use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'TransientStatsdCollector', '\SMW\TransientStatsdCollector' );

			// Explicitly use the DB to access a SqlBagOstuff instance
			$cacheType = CACHE_DB;
			$ttl = 0;

			$transientStatsdCollector = new TransientStatsdCollector(
				$callbackLoader->load( 'BlobStore', TransientStatsdCollector::CACHE_NAMESPACE, $cacheType, $ttl ),
				$id
			);

			return $transientStatsdCollector;
		} );

		/**
		 * @var PropertySpecificationLookup
		 */
		$callbackLoader->registerCallback( 'PropertySpecificationLookup', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'PropertySpecificationLookup', '\SMW\PropertySpecificationLookup' );

			$propertySpecificationLookup = new PropertySpecificationLookup(
				$callbackLoader->singleton( 'CachedPropertyValuesPrefetcher' ),
				$callbackLoader->singleton( 'InMemoryPoolCache' )->getPoolCacheById( PropertySpecificationLookup::POOLCACHE_ID )
			);

			// Uses the language object selected in user preferences. It is one
			// of two global language objects
			$propertySpecificationLookup->setLanguageCode(
				Localizer::getInstance()->getUserLanguage()->getCode()
			);

			return $propertySpecificationLookup;
		} );

		/**
		 * @var PropertyHierarchyLookup
		 */
		$callbackLoader->registerCallback( 'PropertyHierarchyLookup', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'PropertyHierarchyLookup', '\SMW\PropertyHierarchyLookup' );

			$propertyHierarchyLookup = new PropertyHierarchyLookup(
				$callbackLoader->load( 'Store' ),
				$callbackLoader->singleton( 'InMemoryPoolCache' )->getPoolCacheById( PropertyHierarchyLookup::POOLCACHE_ID )
			);

			$propertyHierarchyLookup->setSubcategoryDepth(
				$callbackLoader->load( 'Settings' )->get( 'smwgQSubcategoryDepth' )
			);

			$propertyHierarchyLookup->setSubpropertyDepth(
				$callbackLoader->load( 'Settings' )->get( 'smwgQSubpropertyDepth' )
			);

			return $propertyHierarchyLookup;
		} );

		/**
		 * @var PropertyLabelFinder
		 */
		$callbackLoader->registerCallback( 'PropertyLabelFinder', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'PropertyLabelFinder', '\SMW\PropertyLabelFinder' );

			$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage();

			$propertyLabelFinder = new PropertyLabelFinder(
				$callbackLoader->load( 'Store' ),
				$extraneousLanguage->getPropertyLabels(),
				$extraneousLanguage->getCanonicalPropertyLabels()
			);

			return $propertyLabelFinder;
		} );

		/**
		 * @var TransitionalDiffStore
		 */
		$callbackLoader->registerCallback( 'TransitionalDiffStore', function() use ( $callbackLoader ) {
			$callbackLoader->registerExpectedReturnType( 'TransitionalDiffStore', '\SMW\SQLStore\TransitionalDiffStore' );

			$cacheFactory = $callbackLoader->load( 'CacheFactory' );
			$cacheType = null;

			$transitionalDiffStore = new TransitionalDiffStore(
				$cacheFactory->newMediaWikiCompositeCache( $cacheType ),
				$cacheFactory->getCachePrefix()
			);

			$transitionalDiffStore->setLogger(
				$callbackLoader->singleton( 'MediaWikiLogger' )
			);

			return $transitionalDiffStore;
		} );
	}

}
