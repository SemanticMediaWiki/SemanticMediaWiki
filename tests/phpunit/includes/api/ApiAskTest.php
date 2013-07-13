<?php

namespace SMW\Test;

/**
 * Tests for the ApiAsk class
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
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ApiAsk
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 */
class ApiAskTest extends ApiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\ApiAsk';
	}

	/**
	 * Provides a query array and its expected printrequest array
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			array(
				// #0 Standard query
				array(
					'[[Modification date::+]]',
					'?Modification date',
					'limit=10'
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

			// #1 Query that produces an error
			// Don't mind the error content as it depends on the language
			array(
				array(
					'[[Modification date::+!]]',
					'limit=3'
				),
				array(
					array(
						'error'=> 'foo',
					)
				)
			)
		);
	}

	/**
	 * @test ApiAsk::execute
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $query
	 * @param array $expected
	 */
	public function testExecute( array $query, array $expected ) {

		$results = $this->doApiRequest( array(
				'action' => 'ask',
				'query' => implode( '|', $query )
		) );

		$this->assertInternalType( 'array', $results );

		// If their is no printrequests array we expect an error array
		if ( isset( $results['query']['printrequests'] ) ) {
			$this->assertEquals( $expected, $results['query']['printrequests'] );
		} else {
			$this->assertArrayHasKey( 'error', $results );
		}

	}
}
