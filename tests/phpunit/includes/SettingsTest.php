<?php

namespace SMW\Test;

use SMW\Settings;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Settings
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		Settings::clear();
		parent::tearDown();
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testCanConstruct( array $settings ) {

		$instance = Settings::newFromArray( $settings );

		$this->assertInstanceOf(
			'\SMW\Settings',
			$instance
		);

		$this->assertFalse(
			$instance === Settings::newFromArray( $settings )
		);
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testGet( array $settings ) {

		$instance = Settings::newFromArray( $settings );

		foreach ( $settings as $name => $value ) {
			$this->assertEquals( $value, $instance->get( $name ) );
		}

		$this->assertTrue( true );
	}

	public function testUnknownSettingThrowsException() {

		$instance = Settings::newFromArray( array( 'Foo' => 'bar' ) );

		$this->setExpectedException( '\SMW\Exception\SettingNotFoundException' );
		$instance->get( 'foo' );
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testSet( array $settings ) {

		$instance = Settings::newFromArray( array() );

		foreach ( $settings as $name => $value ) {
			$instance->set( $name, $value );
			$this->assertEquals( $value, $instance->get( $name ) );
		}

		$this->assertTrue( true );
	}

	public function testAdd() {

		$instance = Settings::newFromArray( array() );

		$instance->set( 'foo', 123 );
		$instance->add( 'foo', 456 );

		$this->assertEquals(
			456,
			$instance->get( 'foo' )
		);

		$instance->set( 'bar', array( 123 ) );
		$instance->add( 'bar', array( 456 ) );

		$this->assertEquals(
			array( 123, 456 ),
			$instance->get( 'bar' )
		);
	}

	/**
	 * @dataProvider globalsSettingsProvider
	 */
	public function testNewFromGlobals( array $settings ) {

		$instance = Settings::newFromGlobals();

		// Assert that newFromGlobals is a static instance
		$this->assertTrue(
			$instance === Settings::newFromGlobals()
		);

		// Reset instance
		$instance->clear();
		$this->assertTrue( $instance !== Settings::newFromGlobals() );

		foreach ( $settings as $key => $value ) {
			$this->assertTrue( $instance->has( $key ), "Failed asserting that {$key} exists" );
		}
	}

	/**
	 * @dataProvider nestedSettingsProvider
	 */
	public function testNestedSettingsIteration( $test, $key, $expected ) {

		$instance = Settings::newFromArray( $test );

		$this->assertInternalType( $expected['type'], $instance->get( $key ) );
		$this->assertEquals( $expected['value'], $instance->get( $key ) );
	}

	/**
	 * @return array
	 */
	public function nestedSettingsProvider() {

		$testEnvironment = new TestEnvironment();
		$utilityFactory = $testEnvironment->getUtilityFactory();

		$Foo  = $utilityFactory->createRandomString();
		$Lula = $utilityFactory->createRandomString();
		$Lala = $utilityFactory->createRandomString();

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
			'foo' => 'bar',
			'baz' => 'BAH',
			'bar' => array( '9001' ),
			'foo' => array( '9001', array( 9001, 4.2 ) ),
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

		unset( $settings['smwgDeprecationNotices'] );

		return array( array( $settings ) );
	}

}
