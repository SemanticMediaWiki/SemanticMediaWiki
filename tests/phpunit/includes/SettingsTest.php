<?php

namespace SMW\Test;

use SMW\Settings;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

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

		$instance = Settings::newFromArray( [ 'Foo' => 'bar' ] );

		$this->setExpectedException( '\SMW\Exception\SettingNotFoundException' );
		$instance->get( 'foo' );
	}

	public function testSafeGetOnUnknownSetting() {

		$instance = Settings::newFromArray( [ 'Foo' => 'bar' ] );

		$this->assertFalse(
			$instance->safeGet( 'foo', false )
		);
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testSet( array $settings ) {

		$instance = Settings::newFromArray( [] );

		foreach ( $settings as $name => $value ) {
			$instance->set( $name, $value );
			$this->assertEquals( $value, $instance->get( $name ) );
		}

		$this->assertTrue( true );
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

		$child  = [ 'Lisa', 'Lula', [ 'Lila' ] ];
		$parent = [ 'child' => $child ];

		$Lila = [ 'Lala' => $Lala, 'parent' => $parent ];
		$Bar  = [ 'Lula' => $Lula, 'Lila'   => $Lila ];
		$test = [ 'Foo'  => $Foo,  'Bar'    => $Bar ];

		return [
			[ $test, 'Foo',    [ 'type' => 'string', 'value' => $Foo ] ],
			[ $test, 'Bar',    [ 'type' => 'array',  'value' => $Bar ] ],
			[ $test, 'Lula',   [ 'type' => 'string', 'value' => $Lula ] ],
			[ $test, 'Lila',   [ 'type' => 'array',  'value' => $Lila ] ],
			[ $test, 'Lala',   [ 'type' => 'string', 'value' => $Lala ] ],
			[ $test, 'parent', [ 'type' => 'array',  'value' => $parent ] ],
			[ $test, 'child',  [ 'type' => 'array',  'value' => $child ] ]
		];
	}

	/**
	 * Provides sample data to be tested
	 *
	 * @return array
	 */
	public function settingsProvider() {
		return [ [ [
			'foo' => 'bar',
			'baz' => 'BAH',
			'bar' => [ '9001' ],
			'foo' => [ '9001', [ 9001, 4.2 ] ],
			'~[,,_,,]:3' => [ 9001, 4.2 ],
		] ] ];
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

		return [ [ $settings ] ];
	}

}
