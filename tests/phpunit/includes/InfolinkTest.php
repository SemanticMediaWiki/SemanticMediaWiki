<?php

namespace SMW\Test;

use SMWInfolink;

/**
 * Tests for the SMWInfolink class
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
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class InfolinkTest extends \MediaWikiTestCase {

	/**
	 * Parameter dataProvider
	 *
	 * @return array
	 */
	public function getParameterDataProvider() {
		return array(
			array(
				// #0
				array(
					'format=template',
					'link=none'
				),
				array(
					'format=template/link=none',
					'x=format%3Dtemplate%2Flink%3Dnone'
				)
			),

			// #1 Bug 47010 (space encoding, named args => named%20args)
			array(
				array(
					'format=template',
					'link=none',
					'named args=1'
				),
				array(
					'format=template/link=none/named-20args=1',
					'x=format%3Dtemplate%2Flink%3Dnone%2Fnamed-20args%3D1'
				)
			),
		);
	}

	/**
	 * Test encode parameters
	 *
	 * @covers SMWInfolink::encodeParameters
	 * @dataProvider getParameterDataProvider
	 */
	public function testEncodeParameters( array $params, array $expectedEncode ) {
		$encodeResult = SMWInfolink::encodeParameters( $params, true );
		$this->assertEquals( $expectedEncode[0], $encodeResult );

		$encodeResult = SMWInfolink::encodeParameters( $params, false );
		$this->assertEquals( $expectedEncode[1], $encodeResult );
	}
}
