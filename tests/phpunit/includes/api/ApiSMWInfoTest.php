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
 * @file
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 * @ingroup Api
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class ApiSMWInfoTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(
			array( 'proppagecount' ),
			array( 'propcount' ),
			array( 'querycount' ),
			array( 'usedpropcount' ),
			array( 'declaredpropcount' ),
			array( 'conceptcount' ),
			array( 'querysize' ),
			array( 'subobjectcount' )
		);
	}

	/**
	 * Helper function info return results from the API
	 *
	 * @see https://www.mediawiki.org/wiki/API:Calling_internally
	 *
	 * @return array
	 */
	private function getAPIResults( $queryParameters ) {
		$params = new \FauxRequest(
			array(
				'action' => 'smwinfo',
				'info' => $queryParameters
			)
		);

		$api = new \ApiMain( $params );
		$api->execute();
		return $api->getResultData();
	}

	/**
	 * Test query parameters
	 *
	 * @dataProvider getDataProvider
	 * @since 1.9
	 */
	public function testQueryParameters( $queryParameters ) {

		$data = $this->getAPIResults( $queryParameters );

		// Info array should return with either 0 or > 0
		$this->assertGreaterThanOrEqual( 0, $data['info'][$queryParameters] );
	}

	/**
	 * Test unknown query parameter
	 *
	 * A unknown parameter will produce a warnings array and only valid
	 * parameters will yield an info array
	 *
	 * @since 1.9
	 */
	public function testUnknownQueryParameter() {

		$data = $this->getAPIResults( 'Foo' );
		$this->assertInternalType( 'array', $data['warnings'] );
	}
}
