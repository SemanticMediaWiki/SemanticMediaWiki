<?php

namespace SMW;

use Onoi\BlobStore\BlobStore;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\QueryEngine;
use SMW\QueryFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

/**
 * The prefetcher only contains cached subject list from a computed a query
 * condition. The result is processed before an individual
 * query printer has access to the query result hence it does not interfere
 * with the final string output manipulation.
 *
 * The main objective is to avoid unnecessary computing of results for queries
 * that represent the same query signature. PrintRequests as part of a QueryResult
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
	const VERSION = '0.2';

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
	const STATSD_ID = 'smw:query:store:0.2a';

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
	 * loggerInterface
	 */
	private $logger;

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

		// hits.embedded + hits.nonEmbedded
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
	 * @since 2.5
	 *
	 * @param Query $query
	 *
	 * @return QueryResult|string
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

		// Use the queryId without a subject to reuse the content among other
		// entities that may have embedded a query with the same query signature
		$queryId = $query->getQueryId();

		$container = $this->blobStore->read(
			$this->getHashFrom( $queryId )
		);

		if ( $container->has( 'results' ) ) {
			return $this->newQueryResultFromCache( $queryId, $query, $container );
		}

		$queryResult = $this->queryEngine->getQueryResult( $query );

		$time = round( ( microtime( true ) - $this->start ), 5 );
		$this->log( __METHOD__ . ' from backend in (sec): ' . $time . " ($queryId)" );

		if ( $this->canUse( $query ) && $queryResult instanceof QueryResult ) {
			$this->addQueryResultToCache( $queryResult, $queryId, $container, $query );
		}

		return $queryResult;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIWikiPage|array $list
	 */
	public function resetCacheBy( $item ) {

		if ( !is_array( $item ) ) {
			$item = array( $item );
		}

		$recordStats = false;

		foreach ( $item as $id ) {
			$id = $this->getHashFrom( $id );

			if ( $this->blobStore->exists( $id ) ) {
				$recordStats = true;
				$this->transientStatsdCollector->incr( 'deletes' );
				$this->blobStore->delete( $id );
			}
		}

		if ( $recordStats ) {
			$this->transientStatsdCollector->recordStats( true );
		}
	}

	private function canUse( $query ) {
		return $this->enabledCache && $this->blobStore->canUse() && ( $query->getContextPage() !== null || ( $query->getContextPage() === null && $this->nonEmbeddedCacheLifetime > 0 ) );
	}

	private function newQueryResultFromCache( $queryId, $query, $container ) {

		$results = array();

		$this->transientStatsdCollector->incr(
			( $query->getContextPage() !== null ? 'hits.embedded' : 'hits.nonEmbedded' )
		);

		$this->transientStatsdCollector->calcMedian(
			'medianRetrievalResponseTime.cached',
			round( ( microtime( true ) - $this->start ), 5 )
		);

		foreach ( $container->get( 'results' ) as $hash ) {
			$results[] = DIWikiPage::doUnserialize( $hash );
		}

		$queryResult = $this->queryFactory->newQueryResult(
			$this->store,
			$query,
			$results,
			$container->get( 'continue' )
		);

		$queryResult->setCountValue( $container->get( 'count' ) );
		$queryResult->setFromCache( true );

		$time = round( ( microtime( true ) - $this->start ), 5 );

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

		$time = round( ( microtime( true ) - $this->start ), 5 );

		$this->log( __METHOD__ . ' cache storage (sec): ' . $time . " ($queryId)" );

		return $queryResult;
	}

	private function addToLinkedList( $contextPage, $queryId ) {

		// Ensure that without QueryDependencyLinksStore being enabled recorded
		// subjects related to a query can be discoverable and purged separately
		$container = $this->blobStore->read(
			$this->getHashFrom( $contextPage )
		);

		// If a subject gets purged the the linked list of queries associated
		// with that subject allows for an immediate associated removal
		$container->addToLinkedList(
			$this->getHashFrom( $queryId )
		);

		$this->blobStore->save(
			$container
		);
	}

	private function getHashFrom( $subject ) {

		if ( $subject instanceof DIWikiPage ) {
			$subject = $subject->asBase()->getHash();
		}

		return md5( $subject . self::VERSION );
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
		$this->transientStatsdCollector->init( 'deletes', 0 );
		$this->transientStatsdCollector->init( 'hits', array() );
		$this->transientStatsdCollector->init( 'medianRetrievalResponseTime', array() );
		$this->transientStatsdCollector->init( 'noCache', array() );
		$this->transientStatsdCollector->set( 'meta.version', self::VERSION );
		$this->transientStatsdCollector->set( 'meta.cacheLifetime.embedded', $GLOBALS['smwgQueryResultCacheLifetime'] );
		$this->transientStatsdCollector->set( 'meta.cacheLifetime.nonEmbedded', $GLOBALS['smwgQueryResultNonEmbeddedCacheLifetime'] );
		$this->transientStatsdCollector->init( 'meta.collectionDate.start', $date );
		$this->transientStatsdCollector->set(  'meta.collectionDate.update', $date );
	}

}
