<?php

namespace SMW\Tests\Query;

use SMW\DIWikiPage;
use SMW\Query\QueryResult;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\QueryResult
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryResultTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = [];
		$results = [];

		$this->assertInstanceOf(
			QueryResult::class,
			new QueryResult( $printRequests, $query, $results, $store )
		);
	}

	public function testGetFilterMap() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = [];
		$results = [];

		$instane = new QueryResult(
			$printRequests,
			$query,
			$results,
			$store
		);

		$this->assertInstanceOf(
			'\SMW\Query\Result\FilterMap',
			$instane->getFilterMap()
		);
	}

	public function testVerifyThatAfterSerializeToArrayResultNextCanBeUsed() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = [];

		$results = [
			new DIWikiPage( 'Foo', 0 ),
			new DIWikiPage( 'Bar', 0 )
		];

		$instance = new QueryResult( $printRequests, $query, $results, $store );

		$instance->serializeToArray();

		$this->assertIsArray(

			$instance->getNext()
		);

		$instance->getHash();

		$this->assertIsArray(

			$instance->getNext()
		);
	}

	public function testIsFromCache() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = [];
		$results = [];

		$instance = new QueryResult(
			$printRequests,
			$query,
			$results,
			$store
		);

		$this->assertFalse(
			$instance->isFromCache()
		);

		$instance->setFromCache( true );

		$this->assertTrue(
			$instance->isFromCache()
		);
	}

	public function testGetHash() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = [];
		$results = [];

		$instance = new QueryResult(
			$printRequests,
			$query,
			$results,
			$store
		);

		$this->assertNotSame(
			$instance->getHash( 'quick' ),
			$instance->getHash()
		);
	}

}
