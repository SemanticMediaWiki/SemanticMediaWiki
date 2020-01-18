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

	public function testRegisterChangeListener() {

		$changeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListener' )
			->disableOriginalConstructor()
			->getMock();

		$changeListener->expects( $this->once() )
			->method( 'canTrigger' )
			->with( $this->equalTo( 'Foo' ) )
			->will( $this->returnValue( true ) );

		$changeListener->expects( $this->once() )
			->method( 'setAttrs' )
			->with( $this->equalTo( [ 'Foo' => 'Bar' ] ) );

		$changeListener->expects( $this->once() )
			->method( 'trigger' )
			->with( $this->equalTo( 'Foo' ) );

		$instance = Settings::newFromArray( [] );
		$instance->registerChangeListener( $changeListener );

		$instance->set( 'Foo', 'Bar' );
		$instance->clearChangeListeners();
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
	public function testNewFromGlobals( $setting ) {

		$instance = Settings::newFromGlobals();

		// Assert that newFromGlobals is a static instance
		$this->assertTrue(
			$instance === Settings::newFromGlobals()
		);

		// Reset instance
		$instance->clear();
		$this->assertTrue( $instance !== Settings::newFromGlobals() );

		$this->assertTrue(
			$instance->has( $setting ),
			"Failed asserting that `{$setting}` exists. It could be that `{$setting}`\n" .
			"is invoked by the `LocalSettings.php` or some parameter is using the `smwg` prefix.\n"
		);
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

		foreach ( $settings as $key => $value ) {
			yield [ $key ];
		}
	}

}
