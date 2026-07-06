<?php

namespace SMW\Tests\Unit\Query\Cache;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Query\Cache\CacheStats;
use SMW\Query\Cache\QueryResultContainer;
use SMW\Query\Cache\QueryResultStore;
use SMW\Query\Cache\ResultCache;
use SMW\Query\Query;
use SMW\QueryEngine;
use SMW\QueryFactory;
use SMW\Store;

/**
 * @covers \SMW\Query\Cache\ResultCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ResultCacheTest extends TestCase {

	private $store;
	private $queryFactory;
	private $resultStore;
	private QueryResultContainer $container;
	private $cacheStats;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->queryFactory = $this->getMockBuilder( QueryFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->resultStore = $this->getMockBuilder( QueryResultStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->resultStore
			->method( 'canUse' )
			->willReturn( true );

		$this->container = $this->getMockBuilder( QueryResultContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cacheStats = $this->getMockBuilder( CacheStats::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResultCache::class,
			new ResultCache( $this->store, $this->queryFactory, $this->resultStore, $this->cacheStats )
		);
	}

	public function testGetQueryResultForEmptyQuery() {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine = $this->getMockBuilder( QueryEngine::class )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine->expects( $this->once() )
			->method( 'getQueryResult' )
			->with( $this->identicalTo( $query ) );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->setQueryEngine( $queryEngine );

		$instance->getQueryResult( $query );
	}

	public function testGetQueryResultFromTempCache() {
		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->willReturn( $this->container );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getQueryId' )
			->willReturn( __METHOD__ );

		$query->expects( $this->atLeastOnce() )
			->method( 'getLimit' )
			->willReturn( 100 );

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$queryEngine = $this->getMockBuilder( QueryEngine::class )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine->expects( $this->once() )
			->method( 'getQueryResult' )
			->with( $this->identicalTo( $query ) );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->setQueryEngine( $queryEngine );

		$instance->getQueryResult( $query );

		// Second time called from tempCache
		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheByQueryList() {
		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->invalidateCache( [ 'Foo' ] );
	}

	public function testNoCache() {
		$this->resultStore->expects( $this->never() )
			->method( 'read' );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getLimit' )
			->willReturn( 100 );

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->atLeastOnce() )
			->method( 'getOption' )
			->willReturnCallback( static function ( $key ) use ( $query ) {
				if ( $key === $query::NO_CACHE ) {
					return true;
				}
				return false;
			} );

		$queryEngine = $this->getMockBuilder( QueryEngine::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->setQueryEngine( $queryEngine );
		$instance->getQueryResult( $query );
	}

	public function testMissingQueryEngineThrowsException() {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$this->expectException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheBySubject() {
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( '1d1e1d94a78b9476c8213a16febe2c9b' );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->invalidateCache( $subject );
	}

	public function testPurgeCacheBySubjectWithHasHMutation() {
		$subject = new WikiPage( 'Foo', NS_MAIN );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( '1e5509cfde15f1f569db295e845ce997' );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->setCacheKeyExtension( 'foo' );
		$instance->invalidateCache( $subject );
	}

	public function testPurgeCacheBySubjectWith_QUERY() {
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->atLeastOnce() )
			->method( 'getSubobjectName' )
			->willReturn( '_QUERYfoo' );

		$subject->expects( $this->never() )
			->method( 'asBase' );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->resultStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( 'dc63f8b4cab1bb1214979932b637cdec' );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->invalidateCache( $subject );
	}

	public function testGetStats() {
		$this->cacheStats->expects( $this->once() )
			->method( 'getStats' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->resultStore,
			$this->cacheStats
		);

		$instance->getStats();
	}

}
