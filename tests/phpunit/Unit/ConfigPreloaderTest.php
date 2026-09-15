<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\ConfigPreloader;
use SMW\Exception\ConfigPreloadFileAlreadyLoadedException;
use SMW\Exception\ConfigPreloadFileNotReadableException;
use SMW\Utils\FileFetcher;

/**
 * @covers \SMW\ConfigPreloader
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloaderTest extends TestCase {

	private const FIXTURE_DIR = __DIR__ . '/../Fixtures/ConfigPreloader';
	private const FIXTURE_SETTING = 'configPreloaderFixtureValue';

	private $gl = [];

	protected function setUp(): void {
		foreach ( $GLOBALS as $key => $value ) {
			if ( is_callable( $value ) ) {
				continue;
			}

			$this->gl[$key] = $value;
		}
	}

	protected function tearDown(): void {
		unset( $GLOBALS[self::FIXTURE_SETTING] );

		foreach ( $this->gl as $key => $value ) {
			$GLOBALS[$key] = $value;
		}
	}

	/**
	 * @dataProvider configFileProvider
	 */
	public function testLoadDefaultConfigFrom( $file ) {
		$instance = new ConfigPreloader();

		$this->assertInstanceOf(
			ConfigPreloader::class,
			$instance->loadDefaultConfigFrom( pathinfo( $file, PATHINFO_BASENAME ) )
		);
	}

	/**
	 * @dataProvider configFileProvider
	 */
	public function testLoadConfigFrom( $file ) {
		$instance = new ConfigPreloader();

		$this->assertInstanceOf(
			ConfigPreloader::class,
			$instance->loadConfigFrom( $file )
		);
	}

	public function testLoadingInvalidConfigFile_ThrowsException() {
		$instance = new ConfigPreloader();

		$this->expectException( ConfigPreloadFileNotReadableException::class );
		$instance->loadConfigFrom( 'foo.php' );
	}

	public function testProfileNameWithoutExtensionIsApplied(): void {
		$GLOBALS['smwgPageSpecialProperties'] = [ '_MDAT' ];

		( new ConfigPreloader() )->loadDefaultConfigFrom( 'media' );

		$this->assertContains( '_MIME', $GLOBALS['smwgPageSpecialProperties'] );
	}

	public function testUnknownProfileNameThrowsException(): void {
		$instance = new ConfigPreloader();

		$this->expectException( ConfigPreloadFileNotReadableException::class );
		$instance->loadDefaultConfigFrom( 'no-such-profile' );
	}

	public function testProfileLoadedTwiceIsApplied(): void {
		$instance = new ConfigPreloader();
		$instance->loadConfigFrom( self::FIXTURE_DIR . '/profile.php' );
		unset( $GLOBALS[self::FIXTURE_SETTING] );

		$instance->loadConfigFrom( self::FIXTURE_DIR . '/profile.php' );

		$this->assertSame( 'applied', $GLOBALS[self::FIXTURE_SETTING] );
	}

	public function testProfileAlreadyRequiredElsewhereThrowsException(): void {
		require self::FIXTURE_DIR . '/required-elsewhere.php';
		$instance = new ConfigPreloader();

		$this->expectException( ConfigPreloadFileAlreadyLoadedException::class );
		$instance->loadConfigFrom( self::FIXTURE_DIR . '/required-elsewhere.php' );
	}

	public function configFileProvider() {
		$fileFetcher = new FileFetcher( $GLOBALS['smwgDir'] . '/data/config/' );
		$iterator = $fileFetcher->findByExtension( 'php' );

		yield from $iterator;
	}

}
