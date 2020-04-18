<?php

namespace SMW\Tests\Integration;

use SMW\ConfigPreloader;
use SMW\Utils\FileFetcher;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\ConfigPreloader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloaderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $gl = [];

	protected function setUp() : void {

		foreach ( $GLOBALS as $key => $value ) {
			if ( is_callable( $value ) ) {
				continue;
			}

			$this->gl[$key] = $value;
		}
	}

	protected function tearDown() : void {
		foreach ( $this->gl as $key => $value ) {
			$GLOBALS[$key] = $value;
		}
	}

	/**
	 *@dataProvider configFileProvider
	 */
	public function testLoadDefaultConfigFrom( $file ) {

		$instance = new ConfigPreloader();

		$this->assertInstanceOf(
			ConfigPreloader::class,
			$instance->loadDefaultConfigFrom( pathinfo( $file, PATHINFO_BASENAME ) )
		);
	}

	/**
	 *@dataProvider configFileProvider
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

		$this->expectException( '\SMW\Exception\ConfigPreloadFileNotReadableException' );
		$instance->loadConfigFrom( 'foo.php' );
	}

	public function configFileProvider() {

		$fileFetcher = new FileFetcher( $GLOBALS['smwgDir'] . '/data/config/' );
		$iterator = $fileFetcher->findByExtension( 'php' );

		yield from $iterator;
	}

}
