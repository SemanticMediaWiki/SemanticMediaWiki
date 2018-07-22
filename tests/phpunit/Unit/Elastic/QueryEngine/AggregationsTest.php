<?php

namespace SMW\Tests\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\Aggregations;
use SMW\Elastic\QueryEngine\FieldMapper;

/**
 * @covers \SMW\Elastic\QueryEngine\Aggregations
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class AggregationsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Aggregations::class,
			new Aggregations()
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testResolve( $parameters, $expected ) {

		$instance = new Aggregations( $parameters );

		$this->assertEquals(
			$expected,
			(string)$instance
		);
	}

	public function parametersProvider() {

		$fieldMapper = new FieldMapper();

		yield [
			[],
			'[]'
		];

		yield [
			$fieldMapper->aggs_terms( 'test_1', 'category' ),
			'{"aggregations":{"test_1":{"terms":{"field":"category"}}}}'
		];

		yield [
			$fieldMapper->aggs_terms( 'test_2', 'category', [ 'size' => 5 ] ),
			'{"aggregations":{"test_2":{"terms":{"field":"category","size":5}}}}'
		];

		yield [
			[
				$fieldMapper->aggs_terms( 'test_1', 'foo' ),
				$fieldMapper->aggs_terms( 'test_2', 'bar' )
			],
			'{"aggregations":{"test_1":{"terms":{"field":"foo"}},"test_2":{"terms":{"field":"bar"}}}}'
		];

		yield [
			[
				new Aggregations( $fieldMapper->aggs_terms( 'test_1', 'foo' ) ),
				new Aggregations( $fieldMapper->aggs_terms( 'test_2', 'bar' ) )
			],
			'{"aggregations":{"test_1":{"terms":{"field":"foo"}},"test_2":{"terms":{"field":"bar"}}}}'
		];

		// https://www.elastic.co/blog/intro-to-Aggregations-pt-2-sub-Aggregations
		$aggs = new Aggregations(
			$fieldMapper->aggs_terms( 'all_boroughs', 'borough' )
		);

		$aggs->addSubAggregations(
			new Aggregations(
				$fieldMapper->aggs_terms( 'cause', 'contributing_factor_vehicle', [ 'size' => 3 ] )
			)
		);

		yield [
			$aggs,
			'{"aggregations":{"all_boroughs":{"terms":{"field":"borough"},"aggregations":{"cause":{"terms":{"field":"contributing_factor_vehicle","size":3}}}}}}'
		];

		$cause = new Aggregations(
			$fieldMapper->aggs_terms( 'cause', 'contributing_factor_vehicle', [ 'size' => 3 ] )
		);

		$cause->addSubAggregations(
			new Aggregations(
				$fieldMapper->aggs_date_histogram( 'incidents_per_month', '@timestamp', 'month' )
			)
		);

		$aggs = new Aggregations(
			$fieldMapper->aggs_terms( 'all_boroughs', 'borough' )
		);

		$aggs->addSubAggregations(
			$cause
		);

		yield [
			$aggs,
			'{"aggregations":{"all_boroughs":{"terms":{"field":"borough"},"aggregations":{"cause":{"terms":{"field":"contributing_factor_vehicle","size":3},"aggregations":{"incidents_per_month":{"date_histogram":{"field":"@timestamp","interval":"month"}}}}}}}}'
		];
	}

}
