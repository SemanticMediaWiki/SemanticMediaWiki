<?php

namespace SMW\Tests;

use SMW\CachedQueryResultPrefetcher;
use SMW\DIWikiPage;
use Onoi\BlobStore\BlobStore;

/**
 * @covers \SMW\CachedQueryResultPrefetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class CachedQueryResultPrefetcherTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			CachedQueryResultPrefetcher::class,
			new CachedQueryResultPrefetcher( $store, $queryFactory, $blobStore )
		);
	}

	public function testGetQueryResultForEmptyQuery() {

		$queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

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
			$store,
			$queryFactory,
			$blobStore
		);

		$instance->setQueryEngine( $queryEngine );

		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheByQueryList() {

		$queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new CachedQueryResultPrefetcher(
			$store,
			$queryFactory,
			$blobStore
		);

		$instance->resetCacheBy( array( 'Foo' ) );
	}

	public function testNoCache() {

		$queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->never() )
			->method( 'read' );

		$blobStore->expects( $this->atLeastOnce() )
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

		$query->expects( $this->atLeastOnce() )
			->method( 'getOptionBy' )
			->with( $this->equalTo( $query::NO_CACHE ) )
			->will( $this->returnValue( true ) );

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CachedQueryResultPrefetcher(
			$store,
			$queryFactory,
			$blobStore
		);

		$instance->setQueryEngine( $queryEngine );
		$instance->getQueryResult( $query );
	}

	public function testMissingQueryEngineThrowsException() {

		$queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CachedQueryResultPrefetcher(
			$store,
			$queryFactory,
			$blobStore
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheBySubject() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( $this->equalTo( '39e2606942246606c5daa2ddfec4ace8' ) );

		$instance = new CachedQueryResultPrefetcher(
			$store,
			$queryFactory,
			$blobStore
		);

		$instance->resetCacheBy( $subject );
	}

}
