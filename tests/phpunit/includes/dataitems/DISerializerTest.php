<?php

namespace SMW\Test;

use SMW\DISerializer;
use SMWQueryProcessor;

/**
 * Tests for the SMW\DISerializer class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * DISerializer test is to verify the exported array structure for content
 * consumers such as the API etc.
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class DISerializerTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\DISerializer';
	}

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			array(

				// #1 Standard query
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
			),

			// #2 Query containing a printrequest formatting
			array(
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
			),
		);
	}

	/**
	 * Helper function that returns a SMWQueryResult object
	 *
	 * @return SMWQueryResult
	 */
	private function getQueryResult( $queryString ) {
		list( $query, $formattedParams ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$queryString,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);

		return smwfGetStore()->getQueryResult( $query );
	}

	/**
	 * @test DISerializer::getSerializedQueryResult
	 * @dataProvider getDataProvider
	 *
	 * @since  1.9
	 *
	 * @param array $test
	 * @param array $expected
	 */
	public function testDISerializerQueryResult( array $test, array $expected ) {

		$queryResult = $this->getQueryResult( $test['query'] );
		$this->assertInstanceOf( '\SMWQueryResult', $queryResult );

		$results = DISerializer::getSerializedQueryResult( $queryResult );
		$this->assertInternalType( 'array' , $results );

		//  Compare array structure
		$this->assertEquals( $expected[0], $results['printrequests'][0] );
		$this->assertEquals( $expected[1], $results['printrequests'][1] );
	}

	/**
	 * @test SMWQueryResult::toArray
	 * @dataProvider getDataProvider
	 *
	 * @since  1.9
	 *
	 * @param array $test
	 * @param array $expected
	 */
	public function testQueryResulttoArray( array $test, array $expected ) {

		$queryResult = $this->getQueryResult( $test['query'] );
		$this->assertInstanceOf( '\SMWQueryResult', $queryResult );

		$results = $queryResult->toArray();

		//  Compare array structure
		$this->assertEquals( $expected[0], $results['printrequests'][0] );
		$this->assertEquals( $expected[1], $results['printrequests'][1] );
	}

	/**
	 * @test DISerializer::getSerialization
	 *
	 * @since  1.9
	 */
	public function testNumberSerialization() {

		// Number serialization
		$dataItem = new \SMWDINumber( 1001 );
		$results = DISerializer::getSerialization( $dataItem );
		$this->assertEquals( $results, 1001 );
	}
}
