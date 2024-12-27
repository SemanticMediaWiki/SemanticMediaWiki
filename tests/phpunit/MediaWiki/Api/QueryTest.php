<?php

namespace SMW\Tests\MediaWiki\Api;

use ReflectionClass;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\Utils\MwApiFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Api\Query
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class QueryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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
		$instance = $this->getMockBuilder( '\SMW\MediaWiki\Api\Query' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Api\Query',
			$instance
		);
	}

	public function testQueryAndQueryResult() {
		$instance = $this->getMockBuilder( '\SMW\MediaWiki\Api\Query' )
			->disableOriginalConstructor()
			->getMock();

		$reflector = new ReflectionClass( '\SMW\MediaWiki\Api\Query' );
		$getQuery  = $reflector->getMethod( 'getQuery' );
		$getQuery->setAccessible( true );
		$query = $getQuery->invoke( $instance, '[[Modification date::+]]', [], [] );

		$this->assertInstanceOf(
			'\SMWQuery',
			$query
		);

		$getQueryResult = $reflector->getMethod( 'getQueryResult' );
		$getQueryResult->setAccessible( true );

		$this->assertInstanceOf(
			'\SMWQueryResult',
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

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
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

		$reflector = new ReflectionClass( '\SMW\MediaWiki\Api\Query' );
		$method = $reflector->getMethod( 'addQueryResult' );
		$method->setAccessible( true );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\Api\Query' )
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
