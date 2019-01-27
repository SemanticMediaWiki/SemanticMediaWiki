<?php

namespace SMW\Query\Result;

use Onoi\BlobStore\BlobStore;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\QueryEngine;
use SMW\QueryFactory;
use SMW\Store;
use SMW\Utils\BufferedStatsdCollector;
use SMW\Utils\Timer;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMW\SQLStore\SQLStore;

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
	 * ID used by the bufferedStatsdCollector, requires to be changed in case
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
	 * @var BufferedStatsdCollector
	 */
	private $bufferedStatsdCollector;

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
	private $dependantHashIdExtension = '';

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param QueryFactory $queryFactory
	 * @param BlobStore $blobStore
	 * @param BufferedStatsdCollector $bufferedStatsdCollector
	 */
	public function __construct( Store $store, QueryFactory $queryFactory, BlobStore $blobStore, BufferedStatsdCollector $bufferedStatsdCollector ) {
		$this->store = $store;
		$this->queryFactory = $queryFactory;
		$this->blobStore = $blobStore;
		$this->bufferedStatsdCollector = $bufferedStatsdCollector;
		$this->tempCache = ApplicationFactory::getInstance()->getInMemoryPoolCache()->getPoolCacheById( self::POOLCACHE_ID );

		$this->initStats( date( 'Y-m-d H:i:s' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getStats() {

		$stats = array_filter( $this->bufferedStatsdCollector->getStats(), function( $key ) {
			return $key !== false;
		} );

		if ( !isset( $stats['misses'] ) || ! isset( $stats['hits'] ) ) {
			return $stats;
		}

		$misses = $this->sum( 0, $stats['misses'] );
		$hits = $this->sum( 0, $stats['hits'] );

		$stats['ratio'] = [];
		$stats['ratio']['hit'] = $hits > 0 ? round( $hits / ( $hits + $misses ), 4 ) : 0;
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
	 * @param string|integer $dependantHashIdExtension
	 */
	public function setDependantHashIdExtension( $dependantHashIdExtension ) {
		$this->dependantHashIdExtension = $dependantHashIdExtension;
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
		$this->bufferedStatsdCollector->recordStats();
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
			$this->bufferedStatsdCollector->incr( $this->getNoCacheId( $query ) );
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
	 * @since 3.1
	 *
	 * @param array $ids
	 * @param string $context
	 */
	public function invalidate( array $queryList, $context = '' ) {
		$this->resetCacheBy( $queryList, $context );
	}

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage|array $items
	 * @param string $context
	 */
	public function resetCacheBy( $items, $context = '' ) {

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
				$this->bufferedStatsdCollector->incr( 'deletes.on' . $context );
				$this->blobStore->delete( $id );
			}
		}

		if ( $recordStats ) {
			$this->bufferedStatsdCollector->recordStats();
		}
	}

	private function canUse( $query ) {
		return $this->enabledCache && $this->blobStore->canUse() && ( $query->getContextPage() !== null || ( $query->getContextPage() === null && $this->nonEmbeddedCacheLifetime > 0 ) );
	}

	private function newQueryResultFromCache( $queryId, $query, $container ) {

		$results = [];
		$incrStats = 'hits.Undefined';
		$resolverJournal = null;

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
			$resolverJournal = $queryResult->getResolverJournal();
		} else {

			$incrStats = ( $query->getContextPage() !== null ? 'hits.embedded.' : 'hits.nonEmbedded.' ) . $context;

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

		if ( $resolverJournal !== null ) {
			$queryResult->setResolverJournal( $resolverJournal );
		}

		$time = Timer::getElapsedTime( __CLASS__, 5 );

		$this->bufferedStatsdCollector->incr( $incrStats );

		$this->bufferedStatsdCollector->calcMedian(
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

		$this->bufferedStatsdCollector->incr(
			( $query->getContextPage() !== null ? 'misses.embedded.' : 'misses.nonEmbedded.' ) . $context
		);

		$this->bufferedStatsdCollector->calcMedian(
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
			BufferedStatsdCollector::class,
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

		return md5( $subject . self::VERSION . $this->dependantHashIdExtension );
	}

	private function log( $message, $context = [] ) {

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

		if ( $query->getOption( Query::NO_CACHE ) === true ) {
			$id = 'noCache.byOption';
		}

		if ( ( $context = $query->getOption( Query::PROC_CONTEXT ) ) !== false ) {
			$id .= '.' . $context;
		}

		return $id;
	}

	private function initStats( $date ) {

		$this->bufferedStatsdCollector->shouldRecord( $this->isEnabled() );

		$this->bufferedStatsdCollector->init( 'misses', [] );
		$this->bufferedStatsdCollector->init( 'hits', [] );
		$this->bufferedStatsdCollector->init( 'deletes', [] );
		$this->bufferedStatsdCollector->init( 'noCache', [] );
		$this->bufferedStatsdCollector->init( 'medianRetrievalResponseTime', [] );
		$this->bufferedStatsdCollector->set( 'meta.version', self::VERSION );
		$this->bufferedStatsdCollector->set( 'meta.cacheLifetime.embedded', $GLOBALS['smwgQueryResultCacheLifetime'] );
		$this->bufferedStatsdCollector->set( 'meta.cacheLifetime.nonEmbedded', $GLOBALS['smwgQueryResultNonEmbeddedCacheLifetime'] );
		$this->bufferedStatsdCollector->init( 'meta.collectionDate.start', $date );
		$this->bufferedStatsdCollector->set( 'meta.collectionDate.update', $date );
	}

	// http://stackoverflow.com/questions/3777995/php-array-recursive-sum
	private static function sum( $value, $container ) {
		return $value + ( is_array( $container ) ? array_reduce( $container, 'self::sum' ) : $container );
	}

}
