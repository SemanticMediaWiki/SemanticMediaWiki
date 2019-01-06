<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use Hooks;
use SMW\ApplicationFactory;
use SMW\HashBuilder;
use SMW\RequestOptions;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Utils\Timer;
use SMW\DIWikiPage;
use SMWQuery as Query;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ParserCachePurgeJob extends Job {

	/**
	 * A balanced size that should be carefully monitored in order to not have a
	 * negative impact when running the initial update in online mode.
	 */
	const CHUNK_SIZE = 300;

	/**
	 * Using DB update execution mode to immediately execute the purge which may
	 * cause a surge in DB inserts.
	 */
	const EXEC_DB = 'exec.db';

	/**
	 * Using journal update execution mode to pause the execution and temporary
	 * store until an actual page is viewed.
	 */
	const EXEC_JOURNAL = 'exec.journal';

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
	public function __construct( Title $title, $params = [] ) {
		parent::__construct( 'smw.parserCachePurge', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 */
	public function insert() {

		if (
			$this->hasParameter( 'is.enabled' ) &&
			$this->getParameter( 'is.enabled' ) === false ) {
			return;
		}

		parent::insert();
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.3
	 */
	public function run() {

		Timer::start( __METHOD__ );
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->pageUpdater = $this->applicationFactory->newPageUpdater();

		$count = 0;
		$linksCount = 0;

		if ( $this->hasParameter( 'limit' ) ) {
			$this->limit = $this->getParameter( 'limit' );
		}

		if ( $this->hasParameter( 'offset' ) ) {
			$this->offset = $this->getParameter( 'offset' );
		}

		if ( $this->hasParameter( 'idlist' ) ) {
			$this->purgeTargetLinksFromList( $this->getParameter( 'idlist' ), $count, $linksCount );
		}

		if ( $this->getParameter( 'exec.mode' ) !== self::EXEC_JOURNAL ) {
			$this->pageUpdater->addPage( $this->getTitle() );
			$this->pageUpdater->setOrigin( __METHOD__ );
			$this->pageUpdater->doPurgeParserCacheAsPool();
		}

		Hooks::run( 'SMW::Job::AfterParserCachePurgeComplete', [ $this ] );

		$this->applicationFactory->getMediaWikiLogger()->info(
			[
				'Job',
				"ParserCachePurgeJob",
				"List count:{count}",
				"Links count:{linksCount}",
				"Limit:{limit}",
				"Offset:{offset}",
				"procTime in sec: {procTime}"
			],
			[
				'method'  => __METHOD__,
				'role' => 'user',
				'procTime' => Timer::getElapsedTime( __METHOD__, 7 ),
				'limit'  => $this->limit,
				'offset' => $this->offset,
				'count'  => $count,
				'linksCount'  => $linksCount
			]
		);

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
	private function purgeTargetLinksFromList( $idList, &$listCount, &$linksCount ) {

		if ( is_string( $idList ) && strpos( $idList, '|' ) !== false ) {
			$idList = explode( '|', $idList );
		}

		if ( $idList === [] ) {
			return true;
		}

		$queryDependencyLinksStoreFactory = $this->applicationFactory->singleton(
			'QueryDependencyLinksStoreFactory'
		);

		$queryDependencyLinksStore = $queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$this->applicationFactory->getStore()
		);

		$dependencyLinksUpdateJournal = $queryDependencyLinksStoreFactory->newDependencyLinksUpdateJournal();

		$requestOptions = new RequestOptions();

		// +1 to look ahead
		$requestOptions->setLimit( $this->limit + 1 );
		$requestOptions->setOffset( $this->offset );
		$requestOptions->setOption( 'links.count', 0 );

		$hashList = $queryDependencyLinksStore->findDependencyTargetLinks(
			$idList,
			$requestOptions
		);

		$linksCount = $requestOptions->getOption( 'links.count' );

		// If more results are available then use an iterative increase to fetch
		// the remaining updates by creating successive jobs
		if ( $linksCount > $this->limit ) {
			$job = new self(
				$this->getTitle(),
				[
					'idlist'  => $idList,
					'limit'   => $this->limit,
					'offset'  => $this->offset + self::CHUNK_SIZE,
					'exec.mode' => $this->getParameter( 'exec.mode' )
				]
			);

			$job->run();
		}

		if ( $hashList === [] ) {
			return true;
		}

		list( $hashList, $queryList ) = $this->splitList( $hashList	);
		$listCount = count( $hashList );

		$cachedQueryResultPrefetcher = $this->applicationFactory->singleton(
			'CachedQueryResultPrefetcher'
		);

		$cachedQueryResultPrefetcher->resetCacheBy(
			$queryList,
			'ParserCachePurgeJob'
		);

		if ( $this->getParameter( 'exec.mode' ) === self::EXEC_JOURNAL ) {
			$dependencyLinksUpdateJournal->updateFromList( $hashList, $this->getTitle()->getLatestRevID() );
		} else{
			$this->addPagesToUpdater( $hashList );
		}
	}

	public function splitList( $hashList ) {

		$targetLinksList = [];
		$queryList = [];

		foreach ( $hashList as $hash ) {

			if ( $hash instanceof DIWikiPage ) {
				$hash = $hash->getHash();
			}

			list( $title, $namespace, $iw, $subobjectname ) = explode( '#', $hash, 4 );

			// QueryResultCache stores queries with they queryID = $subobjectname
			if ( strpos( $subobjectname, Query::ID_PREFIX ) !== false ) {
				$queryList[$subobjectname] = true;
			}

			// We make an assumption (as we avoid to query the DB) about that a
			// query is bind to its subject by simply removing the subobject
			// identifier (_QUERY*) and creating the base (or root) subject for
			// the selected target (embedded query)
			$targetLinksList[HashBuilder::createHashIdFromSegments( $title, $namespace, $iw )] = true;
		}

		return [ array_keys( $targetLinksList ), array_keys( $queryList ) ];
	}

	private function addPagesToUpdater( array $hashList ) {
		foreach ( $hashList as $hash ) {
			$this->pageUpdater->addPage(
				HashBuilder::newTitleFromHash( $hash )
			);
		}
	}

}
