<?php

namespace SMW\Tests;

use SMW\DIWikiPage;

use SMWQueryResult as QueryResult;

/**
 * @covers \SMWQueryResult
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryResultTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = array();
		$results = array();

		$this->assertInstanceOf(
			'\SMWQueryResult',
			new QueryResult( $printRequests, $query, $results, $store )
		);
	}

	public function testVerifyThatAfterSerializeToArrayResultNextCanBeUsed() {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$printRequests = array();

		$results = array(
			new DIWikiPage( 'Foo', 0 ),
			new DIWikiPage( 'Bar', 0 )
		);

		$instance = new QueryResult( $printRequests, $query, $results, $store );

		$instance->serializeToArray();

		$this->assertInternalType(
			'array',
			$instance->getNext()
		);

		$instance->getHash();

		$this->assertInternalType(
			'array',
			$instance->getNext()
		);
	}

}
