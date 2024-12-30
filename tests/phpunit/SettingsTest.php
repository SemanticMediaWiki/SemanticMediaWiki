<?php

namespace SMW\Tests;

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
class SettingsTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $hookDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testCanConstruct( array $settings ) {
		$instance = Settings::newFromArray( $settings );

		$this->assertInstanceOf(
			Settings::class,
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

		$this->expectException( '\SMW\Exception\SettingNotFoundException' );
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
			->with( 'Foo' )
			->willReturn( true );

		$changeListener->expects( $this->once() )
			->method( 'setAttrs' )
			->with( [ 'Foo' => 'Bar' ] );

		$changeListener->expects( $this->once() )
			->method( 'trigger' )
			->with( 'Foo' );

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
		$instance = new Settings();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->loadFromGlobals();

		$this->assertTrue(
			$instance->has( $setting ),
			"Failed asserting that `{$setting}` exists. It could be that `{$setting}`\n" .
			"is invoked by the `LocalSettings.php` or some parameter is using the `smwg` prefix.\n"
		);
	}

	public function testReloadAttemptThrowsException() {
		$instance = new Settings();

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->loadFromGlobals();

		$this->expectException( '\SMW\Exception\SettingsAlreadyLoadedException' );
		$instance->loadFromGlobals();
	}

	public function testMung() {
		$instance = Settings::newFromArray( [ 'Foo' => 123 ] );

		$this->assertEquals(
			'123bar',
			$instance->mung( 'Foo', 'bar' )
		);
	}

	public function testMungOnUnknownTypeThrowsException() {
		$instance = Settings::newFromArray( [ 'Foo' => 123 ] );

		$this->expectException( '\RuntimeException' );
		$instance->mung( 'Foo', 456 );
	}

	/**
	 * Provides sample data to be tested
	 *
	 * @return array
	 */
	public function settingsProvider() {
		return [ [ [
			'baz' => 'BAH',
			'bar' => [ '9001' ],
			'foo' => [ '9001', [ 9001, 4.2 ] ],
			'~[,,_,,]:3' => [ 9001, 4.2 ],
		] ] ];
	}

	/**
	 * Provides and collects individual smwg* settings
	 */
	public function globalsSettingsProvider() {
		$settings = array_intersect_key( $GLOBALS,
			array_flip( preg_grep( '/^smwg/', array_keys( $GLOBALS ) ) )
		);

		unset( $settings['smwgDeprecationNotices'] );

		foreach ( $settings as $key => $value ) {
			yield [ $key ];
		}
	}

}
