<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\RequestOptionsProcessor;
use SMWRequestOptions as RequestOptions;
use SMWStringCondition as StringCondition;

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

	public function testGetSQLOptions() {

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;
		$requestOptions->offset = 2;
		$requestOptions->sort = true;

		$expected = [
			'LIMIT'    => 1,
			'OFFSET'   => 2
		];

		$this->assertEquals(
			$expected,
			RequestOptionsProcessor::getSQLOptions( $requestOptions, 'Foo' )
		);
	}

	public function testGetSQLOptionsWithOrderBy() {

		$instance = new RequestOptionsProcessor( $this->store );

		$requestOptions = new RequestOptions();
		$requestOptions->limit = 2;
		$requestOptions->offset = 2;
		$requestOptions->sort = true;

		$expected = [
			'LIMIT'    => 2,
			'OFFSET'   => 2,
			'ORDER BY' => 'Foo'
		];

		$this->assertEquals(
			$expected,
			RequestOptionsProcessor::getSQLOptions( $requestOptions, 'Foo' )
		);
	}

	/**
	 * @dataProvider requestOptionsToSqlConditionsProvider
	 */
	public function testGetSQLConditions( $requestOptions, $valueCol, $labelCol, $expected ) {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->assertEquals(
			$expected,
			RequestOptionsProcessor::getSQLConditions( $this->store, $requestOptions, $valueCol, $labelCol )
		);
	}

	/**
	 * @dataProvider requestOptionsToApplyProvider
	 */
	public function testApplyRequestOptions( $data, $requestOptions, $expected ) {

		$this->assertEquals(
			$expected,
			RequestOptionsProcessor::applyRequestOptions( $this->store, $data, $requestOptions )
		);
	}

	public function requestOptionsToSqlConditionsProvider() {

		$provider = [];

		# 0
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$provider[] = [
			$requestOptions,
			'Foo',
			'',
			' AND Foo >= 1'
		];

		# 1
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foobar', StringCondition::STRCOND_PRE );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' AND Foo >= 1 AND Bar LIKE foobar%'
		];

		# 2
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foobar', StringCondition::STRCOND_PRE, true );
		$requestOptions->addStringCondition( 'foobaz', StringCondition::STRCOND_POST, true );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' AND Foo >= 1 OR Bar LIKE foobar% OR Bar LIKE %foobaz'
		];

		# 3
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foo_bar', StringCondition::COND_EQ );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' AND Foo >= 1 AND Bar = foo_bar'
		];

		# 4
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foo_bar', StringCondition::COND_EQ );
		$requestOptions->addExtraCondition( 'abd = 123' );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' AND Foo >= 1 AND Bar = foo_bar AND abd = 123'
		];

		# 5
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foo_bar', StringCondition::COND_EQ );
		$requestOptions->addExtraCondition( [ 'OR' => 'abd = 123' ] );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' AND Foo >= 1 AND Bar = foo_bar OR abd = 123'
		];

		# 6
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foo_bar', StringCondition::COND_EQ, true, true );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' ( AND Foo >= 1) AND (Bar NOT = foo_bar) '
		];

		# 7
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foo_bar', StringCondition::COND_EQ, true );
		$requestOptions->addStringCondition( 'foobar', StringCondition::COND_POST, true, true );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' ( AND Foo >= 1 OR Bar = foo_bar) AND (Bar NOT LIKE %foobar) '
		];

		# 8
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$requestOptions->addStringCondition( 'foo_bar', StringCondition::COND_EQ, true );
		$requestOptions->addStringCondition( 'foobar', StringCondition::COND_POST, false, true );
		$requestOptions->addStringCondition( 'foobar_ex', StringCondition::COND_EQ, false, true );

		$provider[] = [
			$requestOptions,
			'Foo',
			'Bar',
			' ( ( AND Foo >= 1 OR Bar = foo_bar) AND (Bar NOT LIKE %foobar) ) AND (Bar NOT = foobar_ex) '
		];

		return $provider;
	}

	public function requestOptionsToApplyProvider() {

		$provider = [];

		#0
		$requestOptions = new RequestOptions();
		$requestOptions->boundary = true;

		$provider[] = [
			[
				new \SMWDIBlob( 'Foo' )
			],
			$requestOptions,
			[
				new \SMWDIBlob( 'Foo' )
			]
		];

		#1
		$requestOptions = new RequestOptions();
		$requestOptions->addStringCondition( 'Foo', StringCondition::STRCOND_PRE );

		$provider[] = [
			[
				new \SMWDIBlob( 'Foo' )
			],
			$requestOptions,
			[
				new \SMWDIBlob( 'Foo' )
			]
		];

		#2 String not match
		$requestOptions = new RequestOptions();
		$requestOptions->addStringCondition( 'Bar', StringCondition::STRCOND_POST );

		$provider[] = [
			[
				new \SMWDIBlob( 'Foo' )
			],
			$requestOptions,
			[]
		];

		#3 Limit
		$requestOptions = new RequestOptions();
		$requestOptions->limit = 1;

		$provider[] = [
			[
				new \SMWDIBlob( 'Foo' ),
				new \SMWDIBlob( 'Bar' )
			],
			$requestOptions,
			[
				new \SMWDIBlob( 'Foo' )
			]
		];

		#4 ascending
		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->ascending = true;

		$provider[] = [
			[
				new \SMWDIBlob( 'Foo' ),
				new \SMWDIBlob( 'Bar' )
			],
			$requestOptions,
			[
				new \SMWDIBlob( 'Bar' ),
				new \SMWDIBlob( 'Foo' )
			]
		];

		#5 descending
		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->ascending = false;

		$provider[] = [
			[
				new \SMWDIBlob( 'Foo' ),
				new \SMWDIBlob( 'Bar' )
			],
			$requestOptions,
			[
				new \SMWDIBlob( 'Foo' ),
				new \SMWDIBlob( 'Bar' )
			]
		];

		#6 descending
		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->ascending = false;

		$provider[] = [
			[
				new \SMWDINumber( 10 ),
				new \SMWDINumber( 200 )
			],
			$requestOptions,
			[
				new \SMWDINumber( 200 ),
				new \SMWDINumber( 10 )
			]
		];

		return $provider;
	}

}
