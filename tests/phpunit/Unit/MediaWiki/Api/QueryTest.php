<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\Tests\Utils\MwApiFactory;

use SMW\StoreFactory;
use SMW\ApplicationFactory;

use ReflectionClass;

/**
 * @covers \SMW\MediaWiki\Api\Query
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class QueryTest extends \PHPUnit_Framework_TestCase {

	private $apiFactory;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->apiFactory = new MwApiFactory();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
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
		$query = $getQuery->invoke( $instance, '[[Modification date::+]]', array(), array() );

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
		$test = array(
			'results' => array(
				'Foo' => array(
					'printouts' => array( 'lula' => array( 'lila' ) )
				)
			),
			'printrequests' => array( 'Bar' ),
			'meta' => array( 'count' => 5, 'offset' => 5 )
		);

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'toArray' )
			->will( $this->returnValue( $test ) );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( true ) );

		$apiResult = $this->apiFactory->newApiResult( array() );

		$reflector = new ReflectionClass( '\SMW\MediaWiki\Api\Query' );
		$method = $reflector->getMethod( 'addQueryResult' );
		$method->setAccessible( true );

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\Api\Query' )
			->disableOriginalConstructor()
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'getResult' )
			->will( $this->returnValue( $apiResult ) );

		$method->invoke( $instance, $queryResult );

		// MW 1.25
		$result = method_exists( $apiResult, 'getResultData' ) ? $apiResult->getResultData() : $instance->getData();

		// This came with 1.25, no idea what this suppose to be
		unset( $result['warnings'] );
		unset( $result['_type'] );

		$this->assertInternalType(
			'array',
			$result
		);

		//$this->assertEquals(
		//	array( 'query' => $test, 'query-continue-offset' => 10 ),
		//	$result
		//);
	}

}
