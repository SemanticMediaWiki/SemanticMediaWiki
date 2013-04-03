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
					'query' => array(
						'[[Modification date::+]]',
						'?Modification date',
						'sort=Modification date',
						'order=desc'
					)
				)
			),
		);
	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @covers QueryProcessor::getQuery
	 *
	 * @since 1.9
	 */
	public function testGetQuery( array $setup ) {
		$instance = new QueryProcessor( $setup['outputMode'], $setup['queryContext'], $setup['showMode'] );
		$instance->map( $setup['query'] );

		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @covers QueryProcessor::getResult
	 *
	 * @since 1.9
	 */
	public function testGetResult( array $setup ) {
		$instance = new QueryProcessor( $setup['outputMode'], $setup['queryContext'], $setup['showMode'] );
		$instance->map( $setup['query'] );

		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

}
