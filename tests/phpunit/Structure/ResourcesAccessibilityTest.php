<?php

namespace SMW\Tests\Structure;

use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ResourcesAccessibilityTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 * @covers Resources
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesScriptsFilesAreAccessible( $modules ) {
		$resourceLoader = MediaWikiServices::getInstance()->getResourceLoader();
		$context = Context::newDummyContext();

		if ( version_compare( MW_VERSION, '1.41.0', '>=' ) ) {
			foreach ( array_keys( $modules ) as $name ) {
				$resourceLoaderModule = $resourceLoader->getModule( $name );
				$scripts = $resourceLoaderModule->getScript( $context );

				foreach ( $scripts['plainScripts'] as $key => $value ) {
					$this->assertIsString( $value['content'] );
				}
			}
		} else {
			foreach ( array_keys( $modules ) as $name ) {
				$resourceLoaderModule = $resourceLoader->getModule( $name );

				$this->assertIsString(

					$resourceLoaderModule->getScript( $context )
				);
			}
		}
	}

	/**
	 * @covers Resources
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesStylesFilesAreAccessible( $modules ) {
		$resourceLoader = MediaWikiServices::getInstance()->getResourceLoader();
		$context = Context::newDummyContext();

		if ( version_compare( MW_VERSION, '1.41.0', '>=' ) ) {
			foreach ( array_keys( $modules ) as $name ) {
				$resourceLoaderModule = $resourceLoader->getModule( $name );
				$styles = $resourceLoaderModule->getStyles( $context );

				foreach ( $styles as $key => $value ) {
					$this->assertIsString( $value );
				}
			}
		} else {
			foreach ( array_keys( $modules ) as $name ) {
				$resourceLoaderModule = $resourceLoader->getModule( $name );
				$styles = $resourceLoaderModule->getStyles( $context );

				foreach ( $styles as $style ) {
					$this->assertIsString( $style );
				}
			}
		}
	}

	public static function moduleDataProvider() {
		foreach ( $GLOBALS['smwgResourceLoaderDefFiles'] as $key => $file ) {
			yield [
				include $file,
			];
		}
	}

}
