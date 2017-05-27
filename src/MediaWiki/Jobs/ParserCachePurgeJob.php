<?php

namespace SMW\MediaWiki\Jobs;

use Hooks;
use SMW\ApplicationFactory;
use SMW\HashBuilder;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\RequestOptions;
use SMWQuery as Query;
use Title;
use SMW\Utils\Timer;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ParserCachePurgeJob extends JobBase {

	/**
	 * A balanced size that should be carefully monitored in order to not have a
	 * negative impact when running the initial update in online mode.
	 */
	const CHUNK_SIZE = 300;

	/**
	 * @var ApplicationFactory
	 */
	protected $applicationFactory;

	/**
	 * @var integer
	 */
	private $limit = self::CHUNK_SIZE;

	/**
	 * @var integer
	 */
	private $offset = 0;

	/**
	 * @var PageUpdater
	 */
	protected $pageUpdater;

	/**
	 * @since 2.3
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\ParserCachePurgeJob', $title, $params );
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.3
	 */
	public function run() {

		Timer::start( __METHOD__ );

		$this->pageUpdater = $this->applicationFactory->newPageUpdater();
		$this->store = $this->applicationFactory->getStore();
		$logger = $this->applicationFactory->getMediaWikiLogger();

		if ( $this->hasParameter( 'limit' ) ) {
			$this->limit = $this->getParameter( 'limit' );
		}

		if ( $this->hasParameter( 'offset' ) ) {
			$this->offset = $this->getParameter( 'offset' );
		}

		if ( $this->hasParameter( 'idlist' ) ) {
			$this->findEmbeddedQueryTargetLinksBatches(
				$this->getParameter( 'idlist' ),
				$logger
			);
		}

		$this->pageUpdater->addPage( $this->getTitle() );
		$this->pageUpdater->setOrigin( __METHOD__ );
		$this->pageUpdater->doPurgeParserCacheAsPool();

		Hooks::run( 'SMW::Job::AfterParserCachePurgeComplete', array( $this ) );

		$logger->info( __METHOD__ . ' (procTime in sec: ' . Timer::getElapsedTime( __METHOD__, 7 ) . ')' );

		return true;
	}

	/**
	 * Based on the CHUNK_SIZE, target links are purged in an instant if those
	 * selected entities are < CHUNK_SIZE which should be enough for most
	 * common queries that only share a limited amount of dependencies, yet for
	 * queries that expect a large subject/dependency pool, doing an online update
	 * for all at once is not feasible hence the iterative process of creating
	 * batches that run through the job scheduler.
	 *
	 * @param array|string $idList
	 */
	private function findEmbeddedQueryTargetLinksBatches( $idList, $logger ) {

		if ( is_string( $idList ) && strpos( $idList, '|') !== false ) {
			$idList = explode( '|', $idList );
		}

		if ( $idList === array() ) {
			return true;
		}

		$queryDependencyLinksStoreFactory = new QueryDependencyLinksStoreFactory();

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$this->store
		);

		$requestOptions = new RequestOptions();

		// +1 to look ahead
		$requestOptions->setLimit( $this->limit + 1 );
		$requestOptions->setOffset( $this->offset );
		$requestOptions->targetLinksCount = 0;

		$hashList = $queryDependencyLinksStore->findEmbeddedQueryTargetLinksHashListFrom(
			$idList,
			$requestOptions
		);

		// If more results are available then use an iterative increase to fetch
		// the remaining updates by creating successive jobs
		if ( $requestOptions->targetLinksCount > $this->limit ) {
			$job = new self( $this->getTitle(), array(
				'idlist' => $idList,
				'limit'  => $this->limit,
				'offset' => $this->offset + self::CHUNK_SIZE
			) );

			$job->run();
		}

		if ( $hashList === array() ) {
			return true;
		}

		list( $hashList, $queryList ) = $this->doBuildUniqueTargetLinksHashList(
			$hashList
		);

		$logger->info(
			__METHOD__  .
			" counted: " . count( $hashList ) .
			" targetLinksCount: " . $requestOptions->targetLinksCount .
			" (offset: {$this->offset}  for " . $this->getTitle()->getPrefixedDBKey() . ")"
		);

		$this->applicationFactory->singleton( 'CachedQueryResultPrefetcher' )->resetCacheBy(
			$queryList,
			'ParserCachePurgeJob'
		);

		$this->addPagesToUpdater( $hashList );
	}

	private function doBuildUniqueTargetLinksHashList( $targetLinksHashList ) {

		$uniqueTargetLinksHashList = array();
		$uniqueQueryList = array();

		foreach ( $targetLinksHashList as $targetLinkHash ) {

			list( $title, $namespace, $iw, $subobjectname ) = explode( '#', $targetLinkHash, 4 );

			// QueryResultCache stores queries with they queryID = $subobjectname
			if ( strpos( $subobjectname, Query::ID_PREFIX ) !== false ) {
				$uniqueQueryList[$subobjectname] = true;
			}

			// We make an assumption (as we avoid to query the DB) about that a
			// query is bind to its subject by simply removing the subobject
			// identifier (_QUERY*) and creating the base (or root) subject for
			// the selected target (embedded query)
			$uniqueTargetLinksHashList[HashBuilder::createHashIdFromSegments( $title, $namespace, $iw )] = true;
		}

		return array( array_keys( $uniqueTargetLinksHashList ), array_keys( $uniqueQueryList ) );
	}

	private function addPagesToUpdater( array $hashList ) {
		foreach ( $hashList as $hash ) {
			$this->pageUpdater->addPage(
				HashBuilder::newTitleFromHash( $hash )
			);
		}
	}

}
