<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QueryEngine;
use SMWQuery as Query;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryEngine
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryEngineTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $queryBuilder;
	private $querySegmentListResolver;
	private $engineOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->queryBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QueryBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->querySegmentListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$this->engineOptions = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\EngineOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryEngine',
			new QueryEngine( $this->store, $this->queryBuilder, $this->querySegmentListResolver, $this->engineOptions )
		);
	}

	public function testGetQueryResultForDebugQueryMode() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->queryBuilder->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$this->querySegmentListResolver->expects( $this->any() )
			->method( 'getListOfResolvedQueries' )
			->will( $this->returnValue( array() ) );

		$description = $this->getMockForAbstractClass( '\SMW\Query\Language\Description' );

		$instance = new QueryEngine(
			$this->store,
			$this->queryBuilder,
			$this->querySegmentListResolver,
			$this->engineOptions
		);

		$query = new Query( $description );
		$query->querymode = Query::MODE_DEBUG;

		$this->assertInternalType(
			'string',
			$instance->getQueryResult( $query )
		);
	}

}
