<?php

namespace SMW\Test;

use ResourceLoader;
use ResourceLoaderModule;
use ResourceLoaderContext;

/**
 * Verifies registered resource definitions and files
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Verifies registered resource definitions and files
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ResourcesTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * Helper method that returns an extension path
	 *
	 * @return string
	 */
	private function getSMWResourceModules() {
		return include $GLOBALS['smwgIP'] . '/resources/Resources.php';
	}

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function moduleDataProvider() {
		$resourceLoader = new ResourceLoader();
		$context = ResourceLoaderContext::newDummyContext();
		$modules = $this->getSMWResourceModules();

		return array( array( $modules, $resourceLoader, $context ) );
	}

	/**
	 * Test scripts accessibility
	 * @dataProvider moduleDataProvider
	 *
	 * @param $modules
	 * @param ResourceLoader $resourceLoader
	 * @param $context
	 */
	public function testModulesScriptsFilesAreAccessible( $modules, ResourceLoader $resourceLoader, $context ) {
		foreach ( $modules as $name => $values ){

			// Get module details
			$module = $resourceLoader->getModule( $name );

			// Get scripts per module
			$scripts = $module->getScript( $context );
			$this->assertInternalType( 'string', $scripts );
		}
	}

	/**
	 * Test styles accessibility
	 * @dataProvider moduleDataProvider
	 *
	 * @param $modules
	 * @param ResourceLoader $resourceLoader
	 * @param $context
	 */
	public function testModulesStylesFilesAreAccessible( $modules, ResourceLoader $resourceLoader, $context  ) {

		foreach ( $modules as $name => $values ){

			// Get module details
			$module = $resourceLoader->getModule( $name );

			// Get styles per module
			$styles = $module->getStyles( $context );

			foreach ( $styles as $style ){
			$this->assertInternalType( 'string', $style );
			}
		}
	}
}
