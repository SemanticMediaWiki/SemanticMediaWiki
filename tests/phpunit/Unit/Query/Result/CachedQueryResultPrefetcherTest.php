<?php

namespace SMW\Tests\Query\Result;

use Onoi\BlobStore\BlobStore;
use Onoi\BlobStore\Container;
use SMW\DIWikiPage;
use SMW\Query\Result\CachedQueryResultPrefetcher;
use SMW\Tests\PHPUnitCompat;
use SMW\Query\Cache\CacheStats;

/**
 * @covers \SMW\Query\Result\CachedQueryResultPrefetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class CachedQueryResultPrefetcherTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $queryFactory;
	private $blobStore;
	private $cacheStats;

	protected function setUp() {

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
			CachedQueryResultPrefetcher::class,
			new CachedQueryResultPrefetcher( $this->store, $this->queryFactory, $this->blobStore, $this->cacheStats )
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
			->with($this->identicalTo( $query ) );

		$instance = new CachedQueryResultPrefetcher(
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
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $this->container ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getQueryId' )
			->will( $this->returnValue( __METHOD__ ) );

		$query->expects( $this->atLeastOnce() )
			->method( 'getLimit' )
			->will( $this->returnValue( 100 ) );

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$queryEngine->expects( $this->once() )
			->method( 'getQueryResult' )
			->with($this->identicalTo( $query ) );

		$instance = new CachedQueryResultPrefetcher(
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
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->resetCacheBy( [ 'Foo' ] );
	}

	public function testNoCache() {

		$this->blobStore->expects( $this->never() )
			->method( 'read' );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getLimit' )
			->will( $this->returnValue( 100 ) );

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$query->expects( $this->at( 2 ) )
			->method( 'getOption' )
			->with( $this->equalTo( $query::NO_CACHE ) )
			->will( $this->returnValue( true ) );

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CachedQueryResultPrefetcher(
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

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheBySubject() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( $this->equalTo( '1d1e1d94a78b9476c8213a16febe2c9b' ) );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->resetCacheBy( $subject );
	}

	public function testPurgeCacheBySubjectWithDependantHashIdExtension() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( $this->equalTo( '1e5509cfde15f1f569db295e845ce997' ) );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->setCacheKeyExtension( 'foo' );
		$instance->resetCacheBy( $subject );
	}

	public function testPurgeCacheBySubjectWith_QUERY() {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->atLeastOnce() )
			->method( 'getSubobjectName' )
			->will( $this->returnValue( '_QUERYfoo' ) );

		$subject->expects( $this->never() )
			->method( 'asBase' );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( $this->equalTo( 'dc63f8b4cab1bb1214979932b637cdec' ) );

		$this->cacheStats->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->resetCacheBy( $subject );
	}

	public function testGetStats() {

		$this->cacheStats->expects( $this->once() )
			->method( 'getStats' );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->cacheStats
		);

		$instance->getStats();
	}

}
