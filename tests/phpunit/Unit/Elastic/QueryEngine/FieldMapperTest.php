<?php

namespace SMW\Tests\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\FieldMapper;

/**
 * @covers \SMW\Elastic\QueryEngine\FieldMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FieldMapperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FieldMapper::class,
			new FieldMapper()
		);
	}

	public function testIsPhrase() {

		$this->assertTrue(
			FieldMapper::isPhrase( '"Foo bar"' )
		);

		$this->assertFalse(
			FieldMapper::isPhrase( 'Foo"bar' )
		);
	}

	public function testHasWildcard() {

		$this->assertTrue(
			FieldMapper::hasWildcard( 'Foo*' )
		);

		$this->assertFalse(
			FieldMapper::hasWildcard( 'foo\*' )
		);
	}

	/**
	 * @dataProvider aggregationsProvider
	 */
	public function testAggregations( $method, $params, $expected ) {

		$instance = new FieldMapper();

		$this->assertEquals(
			$expected,
			call_user_func_array( [ $instance, $method ], $params )
		);
	}

	public function aggregationsProvider() {

		yield [
			'aggs',
			[ 'Foo', 'bar' ],
			[ 'aggregations' => [ "Foo" => 'bar' ] ]
		];

		yield [
			'aggs_terms',
			[ 'Foo', 'bar', [] ],
			[ 'Foo' => [ 'terms' => [ "field" => 'bar' ] ] ]
		];

		yield [
			'aggs_significant_terms',
			[ 'Foo', 'bar', [] ],
			[ 'Foo' => [ 'significant_terms' => [ "field" => 'bar' ] ] ]
		];

		yield [
			'aggs_histogram',
			[ 'Foo', 'bar', 100 ],
			[ 'Foo' => [ 'histogram' => [ "field" => 'bar', 'interval' => 100 ] ] ]
		];

		yield [
			'aggs_date_histogram',
			[ 'Foo', 'bar', 100 ],
			[ 'Foo' => [ 'date_histogram' => [ "field" => 'bar', 'interval' => 100 ] ] ]
		];
	}

}
