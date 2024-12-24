<?php

namespace SMW\Tests\Query\Cache;

use SMW\Query\Cache\ResultCache;
use SMW\DIWikiPage;
use SMW\Tests\PHPUnitCompat;
use SMW\Query\Cache\CacheStats;
use Onoi\BlobStore\BlobStore;
use Onoi\BlobStore\Container;

/**
 * @covers \SMW\Query\Cache\ResultCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ResultCacheTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $queryFactory;
	private $blobStore;
	private Container $container;
	private $cacheStats;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->container = $this->getMockBuilder( Container::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cacheStats = $this->getMockBuilder( CacheStats::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResultCache::class,
			new ResultCache( $this->store, $this->queryFactory, $this->blobStore, $this->cacheStats )
		);
	}

	public function testGetQueryResultForEmptyQuery() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine->expects( $this->once() )
			->method( 'getQueryResult' )
			->with( $this->identicalTo( $query ) );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->setQueryEngine( $queryEngine );

		$instance->getQueryResult( $query );
	}

	public function testGetQueryResultFromTempCache() {
		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->willReturn( $this->container );

		$query = $this->getMockBuilder( '\SMWQuery' )
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
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine->expects( $this->once() )
			->method( 'getQueryResult' )
			->with( $this->identicalTo( $query ) );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->setQueryEngine( $queryEngine );

		$instance->getQueryResult( $query );

		// Second time called from tempCache
		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheByQueryList() {
		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->invalidateCache( [ 'Foo' ] );
	}

	public function testNoCache() {
		$this->blobStore->expects( $this->never() )
			->method( 'read' );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getLimit' )
			->willReturn( 100 );

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->at( 2 ) )
			->method( 'getOption' )
			->with( $query::NO_CACHE )
			->willReturn( true );

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->setQueryEngine( $queryEngine );
		$instance->getQueryResult( $query );
	}

	public function testMissingQueryEngineThrowsException() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$this->expectException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheBySubject() {
		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( '1d1e1d94a78b9476c8213a16febe2c9b' );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->invalidateCache( $subject );
	}

	public function testPurgeCacheBySubjectWithHasHMutation() {
		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( '1e5509cfde15f1f569db295e845ce997' );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->setCacheKeyExtension( 'foo' );
		$instance->invalidateCache( $subject );
	}

	public function testPurgeCacheBySubjectWith_QUERY() {
		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->atLeastOnce() )
			->method( 'getSubobjectName' )
			->willReturn( '_QUERYfoo' );

		$subject->expects( $this->never() )
			->method( 'asBase' );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( 'dc63f8b4cab1bb1214979932b637cdec' );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new ResultCache(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
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
			$this->blobStore,
			$this->cacheStats
		);

		$instance->getStats();
	}

}
