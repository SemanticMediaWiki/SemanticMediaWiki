<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\RequestOptionsProcessor;
use SMWRequestOptions as RequestOptions;

/**
 * @covers \SMW\SQLStore\RequestOptionsProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.3
 *
 * @author mwjames
 */
class RequestOptionsProcessorTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\RequestOptionsProcessor',
			new RequestOptionsProcessor( $this->store )
		);
	}

	public function testTransformToSQLOptions() {

		$instance = new RequestOptionsProcessor( $this->store );

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;
		$requestOptions->offset = 2;
		$requestOptions->sort = true;

		$expected = array(
			'LIMIT'    => 1,
			'OFFSET'   => 2,
			'ORDER BY' => 'Foo'
		);

		$this->assertEquals(
			$expected,
			$instance->transformToSQLOptions( $requestOptions, 'Foo' )
		);
	}

	/**
	 * @dataProvider requestOptionsToSqlConditionsProvider
	 */
	public function testTransformToSQLConditions( $requestOptions, $valueCol, $labelCol, $expected ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnSelf() );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new RequestOptionsProcessor( $this->store );

		$this->assertEquals(
			$expected,
			$instance->transformToSQLConditions( $requestOptions, $valueCol, $labelCol )
		);
	}

	/**
	 * @dataProvider requestOptionsToApplyProvider
	 */
	public function testApplyRequestOptionsTo( $data, $requestOptions, $expected ) {

		$instance = new RequestOptionsProcessor( $this->store );

		$this->assertEquals(
			$expected,
			$instance->applyRequestOptionsTo( $data, $requestOptions )
		);
	}

	public function requestOptionsToSqlConditionsProvider() {

		$provider = array();

		# 0
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$provider[] = array(
			$requestOptions,
			'Foo',
			'',
			' AND Foo >= '
		);

		# 1
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foobar', \SMWStringCondition::STRCOND_PRE );

		$provider[] = array(
			$requestOptions,
			'Foo',
			'Bar',
			' AND Foo >=  AND Bar LIKE '
		);

		return $provider;
	}

	public function requestOptionsToApplyProvider() {

		$provider = array();

		# 0
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$provider[] = array(
			array(
				new \SMWDIBlob( 'Foo' )
			),
			$requestOptions,
			array(
				new \SMWDIBlob( 'Foo' )
			)
		);

		return $provider;
	}

}
