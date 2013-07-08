<?php

namespace SMW\Test;

use SMW\Settings;

/**
 * Test for the Settings class
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * @covers \SMW\Settings
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SettingsTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Settings';
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
	 * @test Settings::__construct
	 * @dataProvider settingsProvider
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 */
	public function testConstructor( array $settings ) {

		$instance = $this->getInstance( $settings );

		$this->assertInstanceOf( $this->getClass(), $instance );
		$this->assertFalse( $instance === $this->getInstance( $settings ) );

	}

	/**
	 * @test Settings::get
	 * @dataProvider settingsProvider
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 */
	public function testGet( array $settings ) {

		$instance = $this->getInstance( $settings );

		foreach ( $settings as $name => $value ) {
			$this->assertEquals( $value, $instance->get( $name ) );
		}

		$this->assertTrue( true );

	}

	/**
	 * @test Settings::get
	 *
	 * @since 1.9
	 * @throws InvalidSettingsArgumentException
	 */
	public function testInvalidSettingsArgumentException() {

		$this->setExpectedException( '\SMW\InvalidSettingsArgumentException' );

		$instance = $this->getInstance( array( 'Foo' => 'bar' ) );
		$this->assertEquals( 'bar', $instance->get( 'foo' ) );
	}

	/**
	 * @test Settings::set
	 * @dataProvider settingsProvider
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 */
	public function testSet( array $settings ) {

		$instance = $this->getInstance( array() );

		foreach ( $settings as $name => $value ) {
			$instance->set( $name, $value );
			$this->assertEquals( $value, $instance->get( $name ) );
		}

		$this->assertTrue( true );

	}

	/**
	 * @test Settings::newFromGlobals
	 * @dataProvider globalsSettingsProvider
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 */
	public function testNewFromGlobals( array $settings ) {

		$instance = Settings::newFromGlobals();
		$this->assertInstanceOf( $this->getClass(), $instance );

		// Assert that newFromGlobals is a static instance
		$this->assertTrue( $instance === Settings::newFromGlobals() );

		// Reset instance
		$instance->clear();
		$this->assertTrue( $instance !== Settings::newFromGlobals() );

		foreach ( $settings as $key => $value ) {
			$this->assertTrue( $instance->has( $key ), "Failed asserting that {$key} exists" );
		}
	}

	/**
	 * @test Settings::get
	 * @dataProvider nestedSettingsProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $key
	 * @param $expected
	 */
	public function testNestedSettingsIteration( $test, $key, $expected ) {

		$instance = $this->getInstance( $test );

		$this->assertInternalType( $expected['type'],  $instance->get( $key ) );
		$this->assertEquals( $expected['value'], $instance->get( $key ) );

	}

	/**
	 * Provides sample data to be tested
	 *
	 * @par Example:
	 * @code
	 * array(
	 *	'Foo' => $this->getRandomString(),
	 *	'Bar' => array(
	 *		'Lula' => $this->getRandomString(),
	 *		'Lila' => array(
	 *			'Lala' => $this->getRandomString(),
	 *			'parent' => array(
	 *				'child' => array( 'Lisa', 'Lula', array( 'Lila' ) )
	 *				)
	 *			)
	 *		)
	 *	)
	 * @endcode
	 *
	 * @return array
	 */
	public function nestedSettingsProvider() {

		$Foo  = $this->getRandomString();
		$Lula = $this->getRandomString();
		$Lala = $this->getRandomString();

		$child  = array( 'Lisa', 'Lula', array( 'Lila' ) );
		$parent = array( 'child' => $child );

		$Lila = array( 'Lala' => $Lala, 'parent' => $parent );
		$Bar  = array( 'Lula' => $Lula, 'Lila'   => $Lila );
		$test = array( 'Foo'  => $Foo,  'Bar'    => $Bar );

		return array(
			array( $test, 'Foo',    array( 'type' => 'string', 'value' => $Foo ) ),
			array( $test, 'Bar',    array( 'type' => 'array',  'value' => $Bar ) ),
			array( $test, 'Lula',   array( 'type' => 'string', 'value' => $Lula ) ),
			array( $test, 'Lila',   array( 'type' => 'array',  'value' => $Lila ) ),
			array( $test, 'Lala',   array( 'type' => 'string', 'value' => $Lala ) ),
			array( $test, 'parent', array( 'type' => 'array',  'value' => $parent ) ),
			array( $test, 'child',  array( 'type' => 'array',  'value' => $child ) )
		);
	}

	/**
	 * Provides sample data to be tested
	 *
	 * @return array
	 */
	public function settingsProvider() {
		return array( array( array(
			'',
			'foo' => 'bar',
			'foo' => 'bar', 'baz' => 'BAH',
			'bar' => array( '9001' ),
			'~[,,_,,]:3' => array( 9001, 4.2 ),
		) ) );
	}

	/**
	 * Provides and collects individual smwg* settings
	 *
	 * @return array
	 */
	public function globalsSettingsProvider() {

		$settings = array_intersect_key( $GLOBALS,
			array_flip( preg_grep('/^smwg/', array_keys( $GLOBALS ) ) )
		);

		return array( array( $settings ) );
	}

}
