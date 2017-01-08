<?php

namespace SMW\Query\Result;

use Onoi\BlobStore\BlobStore;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\QueryEngine;
use SMW\QueryFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use SMW\TransientStatsdCollector;
use SMW\ApplicationFactory;
use RuntimeException;

/**
 * The prefetcher only caches the subject list from a computed a query
 * condition. The result is processed before an individual query printer has
 * access to the query result hence it does not interfere with the final string
 * output manipulation.
 *
 * The main objective is to avoid unnecessary computing of results for queries
 * that have the same query signature. PrintRequests as part of a QueryResult
 * object are not cached and are not part of a query signature.
 *
 * Cache eviction is carried out either manually (action=purge) or executed
 * through the QueryDepedencyLinksStore.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class CachedQueryResultPrefetcher implements QueryEngine, LoggerAwareInterface {

	/**
	 * Update this version number when the serialization format
	 * changes.
	 */
	const VERSION = '1';

	/**
	 * Namespace occupied by the BlobStore
	 */
	const CACHE_NAMESPACE = 'smw:query:store';

	/**
	 * ID used by the TransientStatsdCollector, requires to be changed in case
	 * the data schema is modified
	 *
	 * PHP 5.6 can do self::CACHE_NAMESPACE . ':' . self::VERSION
	 */
	const STATSD_ID = 'smw:query:store:1:c:';

	/**
	 * ID for the tempCache
	 */
	const POOLCACHE_ID = 'queryresult.prefetcher';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var QueryFactory
	 */
	private $queryFactory;

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @var QueryEngine
	 */
	private $queryEngine;

	/**
	 * @var TransientStatsdCollector
	 */
	private $transientStatsdCollector;

	/**
	 * @var integer|boolean
	 */
	private $nonEmbeddedCacheLifetime = false;

	/**
	 * @var integer
	 */
	private $start = 0;

	/**
	 * @var boolean
	 */
	private $enabledCache = true;

	/**
	 * @var loggerInterface
	 */
	private $logger;

	/**
	 * Keep a temp cache to hold on query results that aren't stored yet.
	 *
	 * If for example the retrieval is executed in deferred mode then a request
	 * may occur in the same transaction cycle without being stored to the actual
	 * back-end, yet queries with the same signature may have been retrieved
	 * already therefore allow to recall the result from tempCache.
	 *
	 * @var InMemoryCache
	 */
	private $tempCache;

	/**
	 * An internal change to the query execution may occur without being detected
	 * by the Description hash (which is the desired behaviour) and to avoid a
	 * stalled cache on an altered execution plan, use this modifier to generate
	 * a new hash.
	 *
	 * @var string/integer
	 */
	private $hashModifier = '';

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param QueryFactory $queryFactory
	 * @param BlobStore $blobStore
	 * @param TransientStatsdCollector $transientStatsdCollector
	 */
	public function __construct( Store $store, QueryFactory $queryFactory, BlobStore $blobStore, TransientStatsdCollector $transientStatsdCollector ) {
		$this->store = $store;
		$this->queryFactory = $queryFactory;
		$this->blobStore = $blobStore;
		$this->transientStatsdCollector = $transientStatsdCollector;
		$this->tempCache = ApplicationFactory::getInstance()->getInMemoryPoolCache()->getPoolCacheById( self::POOLCACHE_ID );

		$this->initStats( date( 'Y-m-d H:i:s' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getStats() {

		$stats = array_filter( $this->transientStatsdCollector->getStats(), function( $key ) {
			return $key !== false;
		} );

		if ( !isset( $stats['misses'] ) || ! isset( $stats['hits'] ) ) {
			return $stats;
		}

		// hits.embedded + hits.nonEmbedded + hits.tempCache
		$hits = array_sum( $stats['hits'] );
		$stats['ratio'] = array();

		$stats['ratio']['hit'] = $hits > 0 ? round( $hits / ( $hits + $stats['misses'] ), 4 ) : 0;
		$stats['ratio']['miss'] = $hits > 0 ? round( 1 - $stats['ratio']['hit'], 4 ) : 1;

		// Move to last
		$meta = $stats['meta'];
		unset( $stats['meta'] );
		$stats['meta'] = $meta;

		return $stats;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|integer $hashModifier
	 */
	public function setHashModifier( $hashModifier ) {
		$this->hashModifier = $hashModifier;
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @since 2.5
	 *
	 * @param QueryEngine $queryEngine
	 */
	public function setQueryEngine( QueryEngine $queryEngine ) {
		$this->queryEngine = $queryEngine;
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean
	 */
	public function isEnabled() {
		return $this->blobStore->canUse();
	}

	/**
	 * @since 2.5
	 *
	 * @param QueryEngine $queryEngine
	 */
	public function disableCache() {
		$this->enabledCache = false;
	}

	/**
	 * @since 2.5
	 */
	public function recordStats() {
		$this->transientStatsdCollector->recordStats();
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|boolean $nonEmbeddedCacheLifetime
	 */
	public function setNonEmbeddedCacheLifetime( $nonEmbeddedCacheLifetime ) {
		$this->nonEmbeddedCacheLifetime = $nonEmbeddedCacheLifetime;
	}

	/**
	 * @see QueryEngine::getQueryResult
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getQueryResult( Query $query ) {

		if ( !$this->queryEngine instanceof QueryEngine ) {
			throw new RuntimeException( "Missing a QueryEngine instance." );
		}

		if ( !$this->canUse( $query ) || $query->getLimit() < 1 || $query->getOptionBy( Query::NO_CACHE ) === true ) {
			$this->transientStatsdCollector->incr( $this->getNoCacheId( $query ) );
			return $this->queryEngine->getQueryResult( $query );
		}

		$this->start = microtime( true );
		$queryId = $this->getHashFrom( $query->getQueryId() );

		$container = $this->blobStore->read(
			$queryId
		);

		if ( $this->tempCache->contains( $queryId ) || $container->has( 'results' ) ) {
			return $this->newQueryResultFromCache( $queryId, $query, $container );
		}

		$queryResult = $this->queryEngine->getQueryResult( $query );

		$this->tempCache->save(
			$queryId,
			$queryResult
		);

		$this->log(
			__METHOD__ . ' from backend in (sec): ' . round( ( microtime( true ) - $this->start ), 5 ) . " ($queryId)"
		);

		if ( $this->canUse( $query ) && $queryResult instanceof QueryResult ) {
			$this->addQueryResultToCache( $queryResult, $queryId, $container, $query );
		}

		return $queryResult;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage|array $list
	 * @param string $context
	 */
	public function resetCacheBy( $item, $context = '' ) {

		if ( !is_array( $item ) ) {
			$item = array( $item );
		}

		$recordStats = false;
		$context = $context === '' ? 'Undefined' : $context;

		foreach ( $item as $id ) {
			$id = $this->getHashFrom( $id );
			$this->tempCache->delete( $id );

			if ( $this->blobStore->exists( $id ) ) {
				$recordStats = true;
				$this->transientStatsdCollector->incr( 'deletes.on' . $context );
				$this->blobStore->delete( $id );
			}
		}

		if ( $recordStats ) {
			$this->transientStatsdCollector->recordStats();
		}
	}

	private function canUse( $query ) {
		return $this->enabledCache && $this->blobStore->canUse() && ( $query->getContextPage() !== null || ( $query->getContextPage() === null && $this->nonEmbeddedCacheLifetime > 0 ) );
	}

	private function newQueryResultFromCache( $queryId, $query, $container ) {

		$results = array();
		$incrStats = 'hits.Undefined';

		if ( ( $nonEmbeddedContext = $query->getOptionBy( Query::PROC_CONTEXT ) ) === false ) {
			$nonEmbeddedContext = 'Undefined';
		}

		// Check if the tempCache is available for result that have not yet been
		// stored to the cache back-end
		if ( ( $queryResult = $this->tempCache->fetch( $queryId ) ) !== false ) {
			$this->log( __METHOD__ . ' using tempCache ' . "($queryId)" );

			if ( !$queryResult instanceof QueryResult ) {
				return $queryResult;
			}

			$incrStats = 'hits.tempCache.' . ( $query->getContextPage() !== null ? 'embedded' : 'nonEmbedded' );

			$queryResult->reset();
			$results = $queryResult->getResults();

			$hasFurtherResults = $queryResult->hasFurtherResults();
			$countValue = $queryResult->getCountValue();
		} else {

			// - hits.nonEmbedded.SpecialAsk
			// - hits.nonEmbedded.API
			// - hits.nonEmbedded.Undefined
			$incrStats = $query->getContextPage() !== null ? 'hits.embedded' : 'hits.nonEmbedded.' . $nonEmbeddedContext;

			foreach ( $container->get( 'results' ) as $hash ) {
				$results[] = DIWikiPage::doUnserialize( $hash );
			}

			$hasFurtherResults = $container->get( 'continue' );
			$countValue = $container->get( 'count' );
		}

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			$results,
			$hasFurtherResults
		);

		$queryResult->setCountValue( $countValue );
		$queryResult->setFromCache( true );

		$time = round( ( microtime( true ) - $this->start ), 5 );

		$this->transientStatsdCollector->incr( $incrStats );

		$this->transientStatsdCollector->calcMedian(
			'medianRetrievalResponseTime.cached',
			$time
		);

		$this->log( __METHOD__ . ' (sec): ' . $time . " ($queryId)" );

		return $queryResult;
	}

	private function addQueryResultToCache( $queryResult, $queryId, $container, $query ) {

		$this->transientStatsdCollector->incr( 'misses' );

		$this->transientStatsdCollector->calcMedian(
			'medianRetrievalResponseTime.uncached',
			round( ( microtime( true ) - $this->start ), 5 )
		);

		$callback = function() use( $queryResult, $queryId, $container, $query ) {
			$this->doCacheQueryResult( $queryResult, $queryId, $container, $query );
		};

		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate(
			$callback
		);

		$deferredCallableUpdate->setOrigin( __METHOD__ );
		$deferredCallableUpdate->setFingerprint( __METHOD__ . $queryId );
		$deferredCallableUpdate->pushToUpdateQueue();
	}

	private function doCacheQueryResult( $queryResult, $queryId, $container, $query ) {

		$results = array();

		// Keep the simple string representation to avoid unnecessary data cruft
		// during using PHP serialize( ... )
		foreach ( $queryResult->getResults() as $dataItem ) {
			$results[] = $dataItem->getSerialization();
		}

		$container->set( 'results', $results );
		$container->set( 'continue', $queryResult->hasFurtherResults() );
		$container->set( 'count', $queryResult->getCountValue() );

		$queryResult->reset();
		$contextPage = $query->getContextPage();

		if ( $contextPage === null ) {
			$container->setExpiryInSeconds( $this->nonEmbeddedCacheLifetime );
			$hash = 'nonEmbedded';
		} else {
			$this->addToLinkedList( $contextPage, $queryId );
			$hash = $contextPage->getHash();
		}

		$this->blobStore->save(
			$container
		);

		$this->tempCache->delete( $queryId );

		$this->log(
			__METHOD__ . ' cache storage (sec): ' . round( ( microtime( true ) - $this->start ), 5 ) . " ($queryId)"
		);

		return $queryResult;
	}

	private function addToLinkedList( $contextPage, $queryId ) {

		// Ensure that without QueryDependencyLinksStore being enabled recorded
		// subjects related to a query can be discoverable and purged separately
		$container = $this->blobStore->read(
			$this->getHashFrom( $contextPage )
		);

		// If a subject gets purged then the linked list of queries associated
		// with that subject allows for an immediate associated removal
		$container->addToLinkedList( $queryId );

		$this->blobStore->save(
			$container
		);
	}

	private function getHashFrom( $subject ) {

		if ( $subject instanceof DIWikiPage ) {
			$subject = $subject->asBase()->getHash();
		}

		return md5( $subject . self::VERSION . $this->hashModifier );
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

	private function getNoCacheId( $query ) {

		$id = 'noCache.misc';

		if ( !$this->canUse( $query ) ) {
			$id = 'noCache.disabled';
		}

		if ( $query->getLimit() < 1 ) {
			$id = 'noCache.byLimit';
		}

		if ( $query->getOptionBy( Query::NO_CACHE ) === true ) {
			$id = 'noCache.byOption';
		}

		return $id;
	}

	private function initStats( $date ) {

		$this->transientStatsdCollector->shouldRecord( $this->isEnabled() );

		$this->transientStatsdCollector->init( 'misses', 0 );
		$this->transientStatsdCollector->init( 'hits', array() );
		$this->transientStatsdCollector->init( 'deletes', array() );
		$this->transientStatsdCollector->init( 'medianRetrievalResponseTime', array() );
		$this->transientStatsdCollector->init( 'noCache', array() );
		$this->transientStatsdCollector->set( 'meta.version', self::VERSION );
		$this->transientStatsdCollector->set( 'meta.cacheLifetime.embedded', $GLOBALS['smwgQueryResultCacheLifetime'] );
		$this->transientStatsdCollector->set( 'meta.cacheLifetime.nonEmbedded', $GLOBALS['smwgQueryResultNonEmbeddedCacheLifetime'] );
		$this->transientStatsdCollector->init( 'meta.collectionDate.start', $date );
		$this->transientStatsdCollector->set( 'meta.collectionDate.update', $date );
	}

}
