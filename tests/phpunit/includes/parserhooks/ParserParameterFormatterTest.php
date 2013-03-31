<?php

namespace SMW\Test;
use SMW\ParserParameterFormatter;

/**
 * Tests for the SMW\ParserParameterFormatter class.
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
class ParserParameterFormatterTest extends \MediaWikiTestCase {

	public function testSingleton() {
		$instance = ParserParameterFormatter::singleton();

		$this->assertInstanceOf( 'SMW\ParserParameterFormatter', $instance );
		$this->assertTrue( ParserParameterFormatter::singleton() === $instance );
	}

	public function getParametersDataProvider() {
		return array(
			// {{#set:
			// |Has test 1=One
			// }}
			array(
				array('Has test 1=One'),
				array( 'Has test 1' => array( 'One' ) )
				),
			// {{#set:
			// |Has test 2=Two
			// |Has test 2=Three;Four|+sep=;
			// }}
			array(
				array('Has test 2=Two', 'Has test 2=Three;Four', '+sep=;' ),
				array( 'Has test 2' => array( 'Two', 'Three', 'Four' ) )
				),
			// {{#set:
			// |Has test 3=One,Two,Three|+sep
			// |Has test 4=Four
			// }}
			array(
				array('Has test 3=One,Two,Three', '+sep', 'Has test 4=Four' ),
				array( 'Has test 3' => array( 'One', 'Two', 'Three' ), 'Has test 4' => array( 'Four' ) )
				),
			// {{#set:
			// |Has test 5=Test 5-1|Test 5-2|Test 5-3|Test 5-4
			// |Has test 5=Test 5-5
			// }}
			array(
				array('Has test 5=Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', 'Has test 5=Test 5-5' ),
				array( 'Has test 5' => array( 'Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', 'Test 5-5' ) )
				),
			// {{#set:
			// |Has test 6=1+2+3|+sep=+
			// |Has test 7=7
			// |Has test 8=9,10,11,|+sep=
			// }}
			array(
				array('Has test 6=1+2+3', '+sep=+', 'Has test 7=7', 'Has test 8=9,10,11,', '+sep=' ),
				array( 'Has test 6' => array( '1', '2', '3'), 'Has test 7' => array( '7' ), 'Has test 8' => array( '9', '10', '11' ) )
				),
			// {{#set:
			// |Has test 9=One,Two,Three|+sep=;
			// |Has test 10=Four
			// }}
			array(
				array('Has test 9=One,Two,Three', '+sep=;', 'Has test 10=Four' ),
				array( 'Has test 9' => array( 'One,Two,Three' ), 'Has test 10' => array( 'Four' ) )
				),
			// {{#set:
			// |Has test 11=Test 5-1|Test 5-2|Test 5-3|Test 5-4
			// |Has test 12=Test 5-5
			// |Has test 11=9,10,11,|+sep=
			// }}
			array(
				array('Has test 11=Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', 'Has test 12=Test 5-5', 'Has test 11=9,10,11,', '+sep=' ),
				array( 'Has test 11' => array( 'Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', '9', '10', '11' ), 'Has test 12' => array( 'Test 5-5' ) )
				),
		);
	}

	/**
	 * @dataProvider getParametersDataProvider
	 */
	public function testGetParameters( array $params, array $expected ) {
		$results = ParserParameterFormatter::singleton()->getParameters( $params );

		$this->assertTrue( is_array( $results ) );
		$this->assertEquals( $results, $expected );
	}
}