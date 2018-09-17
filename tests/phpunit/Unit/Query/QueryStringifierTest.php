<?php

namespace SMW\Tests\Query;

use SMW\Query\QueryStringifier;

/**
 * @covers \SMW\Query\QueryStringifier
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryStringifierTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider queryProvider
	 */
	public function testToArray( $query, $expected ) {

		$this->assertEquals(
			$expected,
			QueryStringifier::toArray( $query )
		);
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testToJson( $query, $expected ) {

		$this->assertEquals(
			$expected,
			json_decode( QueryStringifier::toJson( $query ), true )
		);
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testGet( $query, $array, $expected ) {

		$this->assertSame(
			$expected,
			QueryStringifier::toString( $query )
		);
	}

	/**
	 * @dataProvider queryProvider
	 */
	public function testRawUrlEncode( $query, $array, $encode, $expected ) {

		$this->assertSame(
			$expected,
			QueryStringifier::rawUrlEncode( $query )
		);
	}

	public function queryProvider() {

		#0
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryString' )
			->will( $this->returnValue( '[[Foo::bar]]' ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getOffset' )
			->will( $this->returnValue( 0 ) );

		yield [
			$query,
			[
				'conditions' => '[[Foo::bar]]',
				'parameters' => [
					'limit' => 42,
					'offset' => 0,
					'mainlabel' => null
				],
				'printouts'  => []
			],
			'[[Foo::bar]]|limit=42|offset=0|mainlabel=',
			'%5B%5BFoo%3A%3Abar%5D%5D%7Climit%3D42%7Coffset%3D0%7Cmainlabel%3D'
		];

		#1
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryString' )
			->will( $this->returnValue( '[[Foo::bar]]' ) );

		$query->expects( $this->any() )
			->method( 'getQuerySource' )
			->will( $this->returnValue( 'Baz' ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getOffset' )
			->will( $this->returnValue( 0 ) );

		yield [
			$query,
			[
				'conditions' => '[[Foo::bar]]',
				'parameters' => [
					'limit' => 42,
					'offset' => 0,
					'mainlabel' => null,
					'source' => 'Baz'
				],
				'printouts'  => []
			],
			'[[Foo::bar]]|limit=42|offset=0|mainlabel=|source=Baz',
			'%5B%5BFoo%3A%3Abar%5D%5D%7Climit%3D42%7Coffset%3D0%7Cmainlabel%3D%7Csource%3DBaz'
		];

		#2
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryString' )
			->will( $this->returnValue( '[[Foo::bar]]' ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getOffset' )
			->will( $this->returnValue( 0 ) );

		$query->expects( $this->any() )
			->method( 'getSortKeys' )
			->will( $this->returnValue( [ 'Foobar' => 'DESC' ] ) );

		yield [
			$query,
			[
				'conditions' => '[[Foo::bar]]',
				'parameters' => [
					'limit' => 42,
					'offset' => 0,
					'mainlabel' => null,
					'sort' => 'Foobar',
					'order' => 'desc'
				],
				'printouts'  => []
			],
			'[[Foo::bar]]|limit=42|offset=0|mainlabel=|sort=Foobar|order=desc',
			'%5B%5BFoo%3A%3Abar%5D%5D%7Climit%3D42%7Coffset%3D0%7Cmainlabel%3D%7Csort%3DFoobar%7Corder%3Ddesc'
		];

		#3
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryString' )
			->will( $this->returnValue( '[[Foo::bar]]' ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getOffset' )
			->will( $this->returnValue( 0 ) );

		$query->expects( $this->any() )
			->method( 'getSortKeys' )
			->will( $this->returnValue( [ 'Foobar' => 'DESC', 'Foobaz' => 'ASC' ] ) );

		yield [
			$query,
			[
				'conditions' => '[[Foo::bar]]',
				'parameters' => [
					'limit' => 42,
					'offset' => 0,
					'mainlabel' => null,
					'sort' => 'Foobar,Foobaz',
					'order' => 'desc,asc'
				],
				'printouts'  => []
			],
			'[[Foo::bar]]|limit=42|offset=0|mainlabel=|sort=Foobar,Foobaz|order=desc,asc',
			'%5B%5BFoo%3A%3Abar%5D%5D%7Climit%3D42%7Coffset%3D0%7Cmainlabel%3D%7Csort%3DFoobar%2CFoobaz%7Corder%3Ddesc%2Casc'
		];

		#4
		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'getSerialisation' )
			->will( $this->returnValue( '?ABC' ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryString' )
			->will( $this->returnValue( '[[Foo::bar]]' ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getOffset' )
			->will( $this->returnValue( 0 ) );

		$query->expects( $this->any() )
			->method( 'getExtraPrintouts' )
			->will( $this->returnValue( [ $printRequest ] ) );

		yield [
			$query,
			[
				'conditions' => '[[Foo::bar]]',
				'parameters' => [
					'limit' => 42,
					'offset' => 0,
					'mainlabel' => null
				],
				'printouts'  => [
					'?ABC'
				]
			],
			'[[Foo::bar]]|?ABC|limit=42|offset=0|mainlabel=',
			'%5B%5BFoo%3A%3Abar%5D%5D%7C%3FABC%7Climit%3D42%7Coffset%3D0%7Cmainlabel%3D'
		];

		#5 (#show returns with an extra =)
		$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
			->disableOriginalConstructor()
			->getMock();

		$printRequest->expects( $this->any() )
			->method( 'getSerialisation' )
			->will( $this->returnValue( '?ABC' ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getQueryString' )
			->will( $this->returnValue( '[[Foo::bar]]' ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$query->expects( $this->any() )
			->method( 'getOffset' )
			->will( $this->returnValue( 0 ) );

		$query->expects( $this->any() )
			->method( 'getExtraPrintouts' )
			->will( $this->returnValue( [ $printRequest ] ) );

		yield [
			$query,
			[
				'conditions' => '[[Foo::bar]]',
				'parameters' => [
					'limit' => 42,
					'offset' => 0,
					'mainlabel' => null
				],
				'printouts'  => [
					'?ABC'
				]
			],
			'[[Foo::bar]]|?ABC|limit=42|offset=0|mainlabel=',
			'%5B%5BFoo%3A%3Abar%5D%5D%7C%3FABC%7Climit%3D42%7Coffset%3D0%7Cmainlabel%3D'
		];
	}

}
