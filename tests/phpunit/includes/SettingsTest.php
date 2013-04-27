<?php

namespace SMW\Test;

use SMW\Settings;
use MWException;

/**
 * Tests for the SMW\Settings class.
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
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */

/**
 * This class tests methods provided by the SMW\Settings class
 *
 * @ingroup SMW
 * @ingroup Test
 */
class SettingsTest extends SemanticMediaWikiTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\Settings';
	}

	/**
	 * Data provider
	 *
	 * @return array
	 */
	public function dataSettingsProvider() {
		// Using $this->arrayWrap( $settingArrays ) crashed PHP on Windows
		return array( array( array(
			'',
			'foo' => 'bar',
			'foo' => 'bar', 'baz' => 'BAH',
			'bar' => array( '9001' ),
			'~[,,_,,]:3' => array( 9001, 4.2 ),
		) ) );
	}

	/**
	 * Helper method that returns a Settings object
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	private function getInstance( array $settings ) {
		return Settings::newFromArray( $settings );
	}

	/**
	 * Test Settings::__construct
	 *
	 * @since 1.9
	 *
	 * @dataProvider dataSettingsProvider
	 * @param array $settings
	 */
	public function testConstructor( array $settings ) {
		$instance = $this->getInstance( $settings );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * Test Settings::get
	 *
	 * @since 1.9
	 *
	 * @dataProvider dataSettingsProvider
	 * @param array $settings
	 */
	public function testGet( array $settings ) {
		$settingsObject = $this->getInstance( $settings );

		foreach ( $settings as $name => $value ) {
			$this->assertEquals( $value, $settingsObject->get( $name ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * Test Settings::set
	 *
	 * @since 1.9
	 *
	 * @dataProvider dataSettingsProvider
	 * @param array $settings
	 */
	public function testSet( array $settings ) {
		$settingsObject = $this->getInstance( array() );

		foreach ( $settings as $name => $value ) {
			$settingsObject->set( $name, $value );
			$this->assertEquals( $value, $settingsObject->get( $name ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * Data provider that collects all individual smwg* settings
	 *
	 * @return array
	 */
	public function dataGlobalsSettingsProvider() {
		$settings = array_intersect_key( $GLOBALS,
			array_flip( preg_grep('/^smwg/', array_keys( $GLOBALS ) ) )
		);

		return array( array( $settings ) );
	}

	/**
	 * Test Settings::newFromGlobals
	 *
	 * @since 1.9
	 *
	 * @dataProvider dataGlobalsSettingsProvider
	 * @param array $settings
	 */
	public function testNewFromGlobals( array $settings ) {
		$instance = Settings::newFromGlobals();
		$this->assertInstanceOf( $this->getClass(), $instance );

		foreach ( $settings as $key => $value ) {
			$this->assertEquals(
				$GLOBALS[$key],
				$instance->get( $key )
			);
		}
	}

	/**
	 * Test exception
	 *
	 * @since 1.9
	 */
	public function testSettingsNameExceptions() {
		$this->setExpectedException( 'MWException' );
		$settingsObject = $this->getInstance( array( 'Foo' => 'bar' ) );
		$this->assertEquals( 'bar', $settingsObject->get( 'foo' ) );
	}
}
