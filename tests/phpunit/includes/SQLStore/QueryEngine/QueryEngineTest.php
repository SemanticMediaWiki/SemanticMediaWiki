<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QueryEngine;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryEngine
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$queryBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$querySegmentListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$engineOptions = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\EngineOptions' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $store, $queryBuilder, $querySegmentListResolver, $engineOptions )
		);
	}

}
