<?php

namespace SMW\Tests\Integration;

use SMW\StoreFactory;
use SMWQueryProcessor;

/**
 * @covers \SMWQueryResult
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @author mwjames
 */
class QueryResultQueryProcessorIntegrationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testCanConstructor( array $test ) {

		$this->assertInstanceOf(
			'\SMWQueryResult',
			$this->getQueryResultFor( $test['query'] )
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testToArray( array $test, array $expected ) {

		$instance = $this->getQueryResultFor( $test['query'] );
		$results  = $instance->toArray();

		$this->assertEquals( $expected[0], $results['printrequests'][0] );
		$this->assertEquals( $expected[1], $results['printrequests'][1] );
	}

	private function getQueryResultFor( $queryString ) {

		list( $query, $formattedParams ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$queryString,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);

		return StoreFactory::getStore()->getQueryResult( $query );
	}

	public function queryDataProvider() {

		$provider = array();

		// #1 Standard query
		$provider[] =array(
			array( 'query' => array(
				'[[Modification date::+]]',
				'?Modification date',
				'limit=10'
				)
			),
			array(
				array(
					'label'=> '',
					'typeid' => '_wpg',
					'mode' => 2,
					'format' => false
				),
				array(
					'label'=> 'Modification date',
					'typeid' => '_dat',
					'mode' => 1,
					'format' => ''
				)
			)
		);

		// #2 Query containing a printrequest formatting
		$provider[] =array(
			array( 'query' => array(
				'[[Modification date::+]]',
				'?Modification date#ISO',
				'limit=10'
				)
			),
			array(
				array(
					'label'=> '',
					'typeid' => '_wpg',
					'mode' => 2,
					'format' => false
				),
				array(
					'label'=> 'Modification date',
					'typeid' => '_dat',
					'mode' => 1,
					'format' => 'ISO'
				)
			)
		);

		return $provider;
	}

}
