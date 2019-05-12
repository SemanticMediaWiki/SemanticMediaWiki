<?php

namespace SMW\Tests\System;

use SMW\ApplicationFactory;
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
			$resourceLoaderModule = $resourceLoader->getModule( $name );

			$this->assertInternalType(
				'string',
				$resourceLoaderModule->getScript( $context )
			);
		}
	}

	/**
	 * @dataProvider moduleDataProvider
	 */
	public function testModulesStylesFilesAreAccessible( $modules, ResourceLoader $resourceLoader, $context ) {

		foreach ( array_keys( $modules ) as $name ) {
			$resourceLoaderModule = $resourceLoader->getModule( $name );
			$styles = $resourceLoaderModule->getStyles( $context );

			foreach ( $styles as $style ) {
				$this->assertInternalType( 'string', $style );
			}
		}
	}

	public function moduleDataProvider() {

		$resourceLoader = ApplicationFactory::getInstance()->create( 'ResourceLoader' );
		$context = ResourceLoaderContext::newDummyContext();

		foreach ( $GLOBALS['smwgResourceLoaderDefFiles'] as $key => $file ) {
			yield [
				include $file,
				$resourceLoader,
				$context
			];
		}
	}

}
