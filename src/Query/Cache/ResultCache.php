<?php

namespace SMW\Query\Cache;

use Onoi\BlobStore\BlobStore;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Store;
use SMW\QueryEngine;
use SMW\QueryFactory;
use SMW\Utils\Timer;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMW\SQLStore\SQLStore;
use SMW\Query\Cache\CacheStats;
use SMW\Query\Excerpts;

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
class ResultCache implements QueryEngine, LoggerAwareInterface {

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
	 * ID used by the CacheStats, requires to be changed in case
	 * the data schema is modified
	 *
	 * PHP 5.6 can do self::CACHE_NAMESPACE . ':' . self::VERSION
	 */
	const STATSD_ID = 'smw:query:store:1:d:';

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
	 * @var CacheStats
	 */
	private $cacheStats;

	/**
	 * @var integer|boolean
	 */
	private $nonEmbeddedCacheLifetime = false;

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
	private $cacheKeyExtension = '';

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param QueryFactory $queryFactory
	 * @param BlobStore $blobStore
	 * @param cacheStats $cacheStats
	 */
	public function __construct( Store $store, QueryFactory $queryFactory, BlobStore $blobStore, CacheStats $cacheStats ) {
		$this->store = $store;
		$this->queryFactory = $queryFactory;
		$this->blobStore = $blobStore;
		$this->cacheStats = $cacheStats;
		$this->tempCache = ApplicationFactory::getInstance()->getInMemoryPoolCache()->getPoolCacheById( self::POOLCACHE_ID );
		$this->cacheStats->shouldRecord( $this->isEnabled() );
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getStats() {
		return $this->cacheStats->getStats();
	}

	/**
	 * @since 2.5
	 *
	 * @param string|integer $cacheKeyExtension
	 */
	public function setCacheKeyExtension( $cacheKeyExtension ) {

		if ( is_array( $cacheKeyExtension ) ) {
			$cacheKeyExtension = implode( '|', $cacheKeyExtension );
		}

		$this->cacheKeyExtension = $cacheKeyExtension;
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
		$this->cacheStats->recordStats();
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

		if ( !$this->canUse( $query ) || $query->getLimit() < 1 || $query->getOption( Query::NO_CACHE ) === true ) {
			$this->cacheStats->incr( $this->noCacheExemption( $query ) );
			return $this->queryEngine->getQueryResult( $query );
		}

		Timer::start( __CLASS__ );

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
			__METHOD__ . ' from backend in (sec): ' . Timer::getElapsedTime( __CLASS__, 5 ) . " ($queryId)"
		);

		if ( $this->canUse( $query ) && $queryResult instanceof QueryResult ) {
			$this->addQueryResultToCache( $queryResult, $queryId, $container, $query );
		}

		return $queryResult;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage|array $items
	 * @param string $context
	 */
	public function invalidateCache( $items, $context = '' ) {

		if ( !$this->blobStore->canUse() ) {
			return;
		}

		if ( !is_array( $items ) ) {
			$items = [ $items ];
		}

		$recordStats = false;
		$context = $context === '' ? 'Undefined' : $context;

		if ( is_array( $context ) ) {
			$context = implode( '.', $context );
		}

		foreach ( $items as $item ) {
			$id = $this->getHashFrom( $item );
			$this->tempCache->delete( $id );

			if ( $this->blobStore->exists( $id ) ) {
				$recordStats = true;
				$this->cacheStats->incr( 'deletes.on' . $context );
				$this->blobStore->delete( $id );
			}
		}

		if ( $recordStats ) {
			$this->cacheStats->recordStats();
		}
	}

	private function canUse( $query ) {

		if ( !$this->enabledCache || !$this->blobStore->canUse() ) {
			return false;
		}

		return $query->getContextPage() !== null || ( $query->getContextPage() === null && $this->nonEmbeddedCacheLifetime > 0 );
	}

	private function newQueryResultFromCache( $queryId, $query, $container ) {

		$results = [];
		$incrStats = 'hits.Undefined';
		$itemJournal = null;

		if ( ( $context = $query->getOption( Query::PROC_CONTEXT ) ) === false ) {
			$context = 'Undefined';
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
			$excerpts = $queryResult->getExcerpts();
			$itemJournal = $queryResult->getItemJournal();
		} else {

			$incrStats = ( $query->getContextPage() !== null ? 'hits.embedded.' : 'hits.nonEmbedded.' ) . $context;

			foreach ( $container->get( 'results' ) as $hash ) {
				$results[] = DIWikiPage::doUnserialize( $hash );
			}

			$hasFurtherResults = $container->get( 'continue' );
			$countValue = $container->get( 'count' );
			$excerpts = $container->get( 'excerpts' );
		}

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			$results,
			$hasFurtherResults
		);

		if ( $excerpts instanceof Excerpts ) {
			$queryResult->setExcerpts( $excerpts );
		}

		$queryResult->setCountValue( $countValue );
		$queryResult->setFromCache( true );

		if ( $itemJournal !== null ) {
			$queryResult->setItemJournal( $itemJournal );
		}

		$time = Timer::getElapsedTime( __CLASS__, 5 );

		$this->cacheStats->incr( $incrStats );

		$this->cacheStats->calcMedian(
			'medianRetrievalResponseTime.cached',
			$time
		);

		$this->log( __METHOD__ . ' (sec): ' . $time . " ($queryId)" );

		return $queryResult;
	}

	private function addQueryResultToCache( $queryResult, $queryId, $container, $query ) {

		if ( ( $context = $query->getOption( Query::PROC_CONTEXT ) ) === false ) {
			$context = 'Undefined';
		}

		$this->cacheStats->incr(
			( $query->getContextPage() !== null ? 'misses.embedded.' : 'misses.nonEmbedded.' ) . $context
		);

		$this->cacheStats->calcMedian(
			'medianRetrievalResponseTime.uncached',
			Timer::getElapsedTime( __CLASS__, 5 )
		);

		$callback = function() use( $queryResult, $queryId, $container, $query ) {
			$this->doCacheQueryResult( $queryResult, $queryId, $container, $query );
		};

		$deferredTransactionalUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate(
			$callback
		);

		$deferredTransactionalUpdate->setOrigin( __METHOD__ );
		$deferredTransactionalUpdate->setFingerprint( __METHOD__ . $queryId );
		$deferredTransactionalUpdate->waitOnTransactionIdle();

		// Make sure that in any event the collector is executed after
		// the process has finished
		$deferredTransactionalUpdate->addPostCommitableCallback(
			CacheStats::class,
			[ $this, 'recordStats' ]
		);

		$deferredTransactionalUpdate->pushUpdate();
	}

	private function doCacheQueryResult( $queryResult, $queryId, $container, $query ) {

		$results = [];

		// Keep the simple string representation to avoid unnecessary data cruft
		// during using PHP serialize( ... )
		foreach ( $queryResult->getResults() as $dataItem ) {
			$results[] = $dataItem->getSerialization();
		}

		$container->set( 'results', $results );
		$container->set( 'continue', $queryResult->hasFurtherResults() );
		$container->set( 'count', $queryResult->getCountValue() );
		$container->set( 'excerpts', $queryResult->getExcerpts() );

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
			__METHOD__ . ' cache storage (sec): ' . Timer::getElapsedTime( __CLASS__, 5 ) . " ($queryId)"
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
			// In case the we detect a _QUERY subobject, use it directly
			if ( ( $subobjectName = $subject->getSubobjectName() ) !== '' && strpos( $subobjectName, Query::ID_PREFIX ) !== false ) {
				$subject = $subobjectName;
			} else {
				$subject = $subject->asBase()->getHash();
			}
		}

		return md5( $subject . self::VERSION . $this->cacheKeyExtension );
	}

	private function log( $message, $context = [] ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

	private function noCacheExemption( $query ) {

		$id = 'noCache.misc';

		if ( !$this->canUse( $query ) ) {
			$id = 'noCache.disabled';
		}

		if ( $query->getLimit() < 1 ) {
			$id = 'noCache.byLimit';
		}

		if ( $query->getOption( Query::NO_CACHE ) === true ) {
			$id = 'noCache.byOption';
		}

		if ( ( $context = $query->getOption( Query::PROC_CONTEXT ) ) !== false ) {
			$id .= '.' . $context;
		}

		return $id;
	}

}
