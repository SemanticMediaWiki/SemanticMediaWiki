<?php

namespace SMW\Tests\Unit\MediaWiki\Api;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\MediaWiki\Api\Query;
use SMW\Query\Query as SMWQuery;
use SMW\Query\QueryResult;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\Utils\MwApiFactory;

/**
 * @covers \SMW\MediaWiki\Api\Query
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class QueryTest extends TestCase {

	private $apiFactory;
	private $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->apiFactory = new MwApiFactory();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown(): void {
		ApplicationFactory::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$instance = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			Query::class,
			$instance
		);
	}

	public function testQueryAndQueryResult() {
		$instance = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$reflector = new ReflectionClass( Query::class );
		$getQuery  = $reflector->getMethod( 'getQuery' );
		$query = $getQuery->invoke( $instance, '[[Modification date::+]]', [], [] );

		$this->assertInstanceOf(
			SMWQuery::class,
			$query
		);

		$getQueryResult = $reflector->getMethod( 'getQueryResult' );

		$this->assertInstanceOf(
			QueryResult::class,
			$getQueryResult->invoke( $instance, $query )
		);
	}

	public function testAddQueryResultOnMockStore() {
		// Minimalistic test case to verify executability
		// For a full coverage, use Api\QueryResultFormatterTest
		$test = [
			'results' => [
				'Foo' => [
					'printouts' => [ 'lula' => [ 'lila' ] ]
				]
			],
			'printrequests' => [ 'Bar' ],
			'meta' => [ 'count' => 5, 'offset' => 5 ]
		];

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'toArray' )
			->willReturn( $test );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->willReturn( [] );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'hasFurtherResults' )
			->willReturn( true );

		$apiResult = $this->apiFactory->newApiResult( [] );

		$reflector = new ReflectionClass( Query::class );
		$method = $reflector->getMethod( 'addQueryResult' );

		$instance = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'getResult' )
			->willReturn( $apiResult );

		$method->invoke( $instance, $queryResult );

		$result = $apiResult->getResultData();

		// This came with 1.25, no idea what this suppose to be
		unset( $result['warnings'] );
		unset( $result['_type'] );

		$this->assertIsArray(

			$result
		);

		// $this->assertEquals(
		//	array( 'query' => $test, 'query-continue-offset' => 10 ),
		//	$result
		//);
	}

}
