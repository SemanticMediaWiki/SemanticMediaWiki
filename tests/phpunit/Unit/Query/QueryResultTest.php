<?php

namespace SMW\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\Query\Result\FilterMap;
use SMW\Store;

/**
 * @covers \SMW\Query\QueryResult
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class QueryResultTest extends TestCase {

	public function testCanConstruct() {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
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
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
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
			FilterMap::class,
			$instane->getFilterMap()
		);
	}

	public function testVerifyThatAfterSerializeToArrayResultNextCanBeUsed() {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = [];

		$results = [
			new WikiPage( 'Foo', 0 ),
			new WikiPage( 'Bar', 0 )
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
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
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
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( Store::class )
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
