<?php

namespace SMW\Tests;

use SMW\CachedQueryResultPrefetcher;
use SMW\DIWikiPage;
use SMW\TransientStatsdCollector;
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

	private $store;
	private $queryFactory;
	private $blobStore;
	private $transientStatsdCollector;

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

		$this->transientStatsdCollector = $this->getMockBuilder( TransientStatsdCollector::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CachedQueryResultPrefetcher::class,
			new CachedQueryResultPrefetcher( $this->store, $this->queryFactory, $this->blobStore, $this->transientStatsdCollector )
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
			$this->transientStatsdCollector
		);

		$instance->setQueryEngine( $queryEngine );

		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheByQueryList() {

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->transientStatsdCollector
		);

		$instance->resetCacheBy( array( 'Foo' ) );
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

		$query->expects( $this->atLeastOnce() )
			->method( 'getOptionBy' )
			->with( $this->equalTo( $query::NO_CACHE ) )
			->will( $this->returnValue( true ) );

		$queryEngine = $this->getMockBuilder( '\SMW\QueryEngine' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->transientStatsdCollector
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
			$this->transientStatsdCollector
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getQueryResult( $query );
	}

	public function testPurgeCacheBySubject() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with( $this->equalTo( '39e2606942246606c5daa2ddfec4ace8' ) );

		$this->transientStatsdCollector->expects( $this->once() )
			->method( 'recordStats' );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->transientStatsdCollector
		);

		$instance->resetCacheBy( $subject );
	}

	public function testGetStats() {

		$this->transientStatsdCollector->expects( $this->once() )
			->method( 'getStats' )
			->will( $this->returnValue( array() ) );

		$instance = new CachedQueryResultPrefetcher(
			$this->store,
			$this->queryFactory,
			$this->blobStore,
			$this->transientStatsdCollector
		);

		$this->assertInternalType(
			'array',
			$instance->getStats()
		);
	}

}
