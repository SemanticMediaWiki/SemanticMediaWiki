<?php

namespace SMW\Tests\Structure;

use MediaWiki\MediaWikiServices;
use SMW\Services\ServicesFactory as ApplicationFactory;
use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ResourcesAccessibilityTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesScriptsFilesAreAccessible( $modules ) {
		$resourceLoader = MediaWikiServices::getInstance()->getResourceLoader();
		$context = ResourceLoaderContext::newDummyContext();

		foreach ( array_keys( $modules ) as $name ) {
			$resourceLoaderModule = $resourceLoader->getModule( $name );

			$this->assertIsString(

				$resourceLoaderModule->getScript( $context )
			);
		}
	}

	/**
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesStylesFilesAreAccessible( $modules ) {
		$resourceLoader = MediaWikiServices::getInstance()->getResourceLoader();
		$context = ResourceLoaderContext::newDummyContext();

		foreach ( array_keys( $modules ) as $name ) {
			$resourceLoaderModule = $resourceLoader->getModule( $name );
			$styles = $resourceLoaderModule->getStyles( $context );

			foreach ( $styles as $style ) {
				$this->assertIsString( $style );
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
