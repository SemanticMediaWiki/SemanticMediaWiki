<?php

namespace SMW\Test;

/**
 * Tests for the ApiSMWInfo class
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
 * Tests for the ApiSMWInfo class
 * @covers \ApiSMWInfo
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 */
class ApiSMWInfoTest extends ApiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\ApiSMWInfo';
	}

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			array( 'proppagecount',     'integer' ),
			array( 'propcount',         'integer' ),
			array( 'querycount',        'integer' ),
			array( 'usedpropcount',     'integer' ),
			array( 'declaredpropcount', 'integer' ),
			array( 'conceptcount',      'integer' ),
			array( 'querysize',         'integer' ),
			array( 'subobjectcount',    'integer' ),
			array( 'formatcount',       'array'   )
		);
	}

	/**
	 * @test ApiSMWInfo::execute
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $query
	 * @param array $expectedPrintrequests
	 */
	public function testExecute( $queryParameters, $expectedType ) {
		$result = $this->doApiRequest( array(
				'action' => 'smwinfo',
				'info' => $queryParameters
		) );

		// Works only after SMW\StatisticsAggregator is available
		//$this->assertInternalType( $expectedType, $result['info'][$queryParameters] );

		// Info array should return with either 0 or > 0 for integers
		if ( $expectedType === 'integer' ) {
			$this->assertGreaterThanOrEqual( 0, $result['info'][$queryParameters] );
		} else {
			$this->assertInternalType( 'array', $result['info'][$queryParameters] );
		}
	}

	/**
	 * @test ApiSMWInfo::execute (Test unknown query parameter)
	 *
	 * Only valid parameters will yield an info array while an unknown parameter
	 * will produce a "warnings" array.
	 *
	 * @since 1.9
	 */
	public function testUnknownQueryParameter() {
		$data = $this->doApiRequest( array(
				'action' => 'smwinfo',
				'info' => 'Foo'
		) );
		$this->assertInternalType( 'array', $data['warnings'] );
	}
}
