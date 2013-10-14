<?php

namespace SMW\Test;

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
class QueryResultTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMWQueryResult';
	}

	/**
	 * Helper method that returns a QueryResult object
	 *
	 * @since 1.9
	 */
	private function newQueryResultOnSQLStore( $queryString ) {

		$this->runOnlyOnSQLStore();

		list( $query, $formattedParams ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$queryString,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);

		return \SMW\StoreFactory::getStore()->getQueryResult( $query );
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testConstructor( array $test ) {
		$this->assertInstanceOf( $this->getClass(), $this->newQueryResultOnSQLStore( $test['query'] ) );
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since  1.9
	 */
	public function testToArray( array $test, array $expected ) {

		$instance = $this->newQueryResultOnSQLStore( $test['query'] );
		$results  = $instance->toArray();

		//  Compare array structure
		$this->assertEquals( $expected[0], $results['printrequests'][0] );
		$this->assertEquals( $expected[1], $results['printrequests'][1] );
	}

	/**
	 * @return array
	 */
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
