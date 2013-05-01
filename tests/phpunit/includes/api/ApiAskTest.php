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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the ApiAsk class
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
		return '\ApiAsk';
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
		);
	}

	/**
	 * @test ApiAsk::execute
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $query
	 * @param array $expectedPrintrequests
	 */
	public function testExecute( array $query, array $expectedPrintrequests ) {
		$results = $this->doApiRequest( array(
				'action' => 'ask',
				'query' => implode( '|', $query )
		) );

		$this->assertInternalType( 'array', $results );
		$this->assertEquals( $expectedPrintrequests, $results['query']['printrequests'] );
	}
}
