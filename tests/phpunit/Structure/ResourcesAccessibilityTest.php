<?php

namespace SMW\Tests\Structure;

use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ResourcesAccessibilityTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers \MediaWiki\ResourceLoader\Module
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesScriptsFilesAreAccessible( $modules ) {
		$resourceLoader = MediaWikiServices::getInstance()->getResourceLoader();
		$context = Context::newDummyContext();

		foreach ( array_keys( $modules ) as $name ) {
			$resourceLoaderModule = $resourceLoader->getModule( $name );
			$scripts = $resourceLoaderModule->getScript( $context );

			foreach ( $scripts['plainScripts'] as $key => $value ) {
				$this->assertIsString( $value['content'] );
			}
		}
	}

	/**
	 * @covers \MediaWiki\ResourceLoader\Module
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesStylesFilesAreAccessible( $modules ) {
		$resourceLoader = MediaWikiServices::getInstance()->getResourceLoader();
		$context = Context::newDummyContext();

		foreach ( array_keys( $modules ) as $name ) {
			$resourceLoaderModule = $resourceLoader->getModule( $name );
			$styles = $resourceLoaderModule->getStyles( $context );

			foreach ( $styles as $key => $value ) {
				$this->assertIsString( $value );
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
