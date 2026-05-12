<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\Exception\SettingNotFoundException;
use SMW\Exception\SettingsAlreadyLoadedException;
use SMW\Listener\ChangeListener\ChangeListener;
use SMW\MediaWiki\HookDispatcher;
use SMW\Settings;

/**
 * @covers \SMW\Settings
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SettingsTest extends TestCase {

	private $hookDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
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

		$this->expectException( SettingNotFoundException::class );
		$instance->get( 'foo' );
	}

	public function testSafeGetOnUnknownSetting() {
		$instance = Settings::newFromArray( [ 'Foo' => 'bar' ] );

		$this->assertFalse(
			$instance->safeGet( 'foo', false )
		);
	}

	public function testRegisterChangeListener() {
		$changeListener = $this->getMockBuilder( ChangeListener::class )
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

		$this->expectException( SettingsAlreadyLoadedException::class );
		$instance->loadFromGlobals();
	}

	/**
	 * Both legacy SMW_* integer constants and the new string / array-of-strings
	 * form must produce identical internal state once Settings::loadFromGlobals()
	 * routes the registered keys through LegacyConstantNormalizer (#6586).
	 */
	public function testLoadFromGlobals_factboxStringForm_internalRepresentation() {
		$savedShow = $GLOBALS['smwgShowFactbox'] ?? null;
		$savedFeatures = $GLOBALS['smwgFactboxFeatures'] ?? null;

		try {
			$GLOBALS['smwgShowFactbox'] = 'nonempty';
			$GLOBALS['smwgFactboxFeatures'] = [ 'cache', 'purge-refresh' ];

			$instance = new Settings();
			$instance->setHookDispatcher( $this->hookDispatcher );
			$instance->loadFromGlobals();

			$this->assertSame( SMW_FACTBOX_NONEMPTY, $instance->get( 'smwgShowFactbox' ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_CACHE ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_PURGE_REFRESH ) );
			$this->assertFalse( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_DISPLAY_SUBOBJECT ) );
		} finally {
			$GLOBALS['smwgShowFactbox'] = $savedShow;
			$GLOBALS['smwgFactboxFeatures'] = $savedFeatures;
		}
	}

	/**
	 * Locks in the current behaviour of legacy SMW_* integer-constant config values
	 * for the factbox settings, ahead of the string-config migration (#6586). The
	 * same assertions must hold after the normalizer is wired in.
	 */
	public function testLoadFromGlobals_factboxLegacyConstants_internalRepresentation() {
		$savedShow = $GLOBALS['smwgShowFactbox'] ?? null;
		$savedFeatures = $GLOBALS['smwgFactboxFeatures'] ?? null;

		try {
			$GLOBALS['smwgShowFactbox'] = SMW_FACTBOX_NONEMPTY;
			$GLOBALS['smwgFactboxFeatures'] = SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH;

			$instance = new Settings();
			$instance->setHookDispatcher( $this->hookDispatcher );
			$instance->loadFromGlobals();

			$this->assertSame( SMW_FACTBOX_NONEMPTY, $instance->get( 'smwgShowFactbox' ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_CACHE ) );
			$this->assertTrue( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_PURGE_REFRESH ) );
			$this->assertFalse( $instance->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_DISPLAY_SUBOBJECT ) );
		} finally {
			$GLOBALS['smwgShowFactbox'] = $savedShow;
			$GLOBALS['smwgFactboxFeatures'] = $savedFeatures;
		}
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
