<?php

namespace SMW\Test;

use SMW\QueryProcessor;

/**
 * Tests for the SMW\QueryProcessor class
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
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWQuery
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class QueryProcessorTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// #0
			array(
				array(
					'outputMode' => SMW_OUTPUT_WIKI,
					'queryContext' => QueryProcessor::INLINE_QUERY,
					'showMode' => false,
					'query' => '[[Modification date::+]]|?Modification date|sort=Modification date|order=desc'
				)
			),
		);
	}

	/**
	 * Helper ...
	 *
	 * @return array
	 */
	public function toArray( $string ){
		return preg_split( "/(?<=[^\|])\|(?=[^\|])/", $string );
	}

	/**
	 * Helper method
	 *
	 * @return SMW\QueryProcessor
	 */
	private function getInstance( $outputMode, $queryContext, $showMode ) {
		//return new QueryProcessor( $outputMode, $queryContext, $showMode  );
	}

	/**
	 * Test instance
	 *
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testConstructor( array $setup ) {
		//$instance = $this->getInstance( $setup['outputMode'], $setup['queryContext'], $setup['showMode'] );
		//$this->assertInstanceOf( 'SMW\QueryProcessor', $instance );
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Test getQuery()
	 *
	 * @covers SMW\QueryProcessor::getQuery
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetQuery( array $setup ) {
		//$instance = $this->getInstance( $setup['outputMode'], $setup['queryContext'], $setup['showMode'] );
		//$instance->map( $this->toArray( $setup['query'] ) );
		//$this->assertInstanceOf( 'SMWQuery', $instance->getQuery() );

		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Test getResult()
	 *
	 * @covers SMW\QueryProcessor::getResult
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetResult( array $setup ) {
		//$instance = $this->getInstance( $setup['outputMode'], $setup['queryContext'], $setup['showMode'] );
		//$instance->map( $this->toArray( $setup['query'] ) );
		//$this->assertTrue( is_string( $instance->getResult() ) );

		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

}
