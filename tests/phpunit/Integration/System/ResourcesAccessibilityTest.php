<?php

namespace SMW\Tests\System;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ResourcesAccessibilityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesScriptsFilesAreAccessible( $modules, ResourceLoader $resourceLoader, $context ) {

		foreach ( array_keys( $modules ) as $name ) {
			$this->assertInternalType(
				'string',
				$resourceLoader->getModule( $name )->getScript( $context )
			);
		}
	}

	/**
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesStylesFilesAreAccessible( $modules, ResourceLoader $resourceLoader, $context ) {

		foreach ( array_keys( $modules ) as $name ) {

			$styles = $resourceLoader->getModule( $name )->getStyles( $context );

			foreach ( $styles as $style ) {
				$this->assertInternalType( 'string', $style );
			}
		}
	}

	public function moduleDataProvider() {

		$resourceLoader = new ResourceLoader();
		$context = ResourceLoaderContext::newDummyContext();

		foreach ( $GLOBALS['smwgResourceLoaderDefFiles'] as $key => $file ) {
			$providers[] = [
				include $file,
				$resourceLoader,
				$context
			];
		}

		return $providers;
	}

}
