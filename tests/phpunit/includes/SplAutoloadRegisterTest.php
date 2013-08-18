<?php

namespace SMW\Test;

/**
 * Tests for the spl_autoload_register
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Verifies registered classes (spl_autoload_register) against
 * classes that are accessible to avoid misspellings etc.
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SplAutoloadRegisterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @since 1.9
	 */
	public function testVerifyAutoloadClasses() {

		$classes = include( __DIR__ . '../../../../' . 'SemanticMediaWiki.classes.php' );

		foreach ( $classes as $class => $path ) {

			// class_exists does not return TRUE for defined interfaces,
			// need to use interface_exists() instead
			if ( interface_exists( $class ) ) {
				$this->assertTrue(
					interface_exists( $class ),
					"Failed asserting that interface {$class} exists"
				);
			} else {
				$this->assertTrue(
					class_exists( $class ),
					"Failed asserting that class {$class} exists"
				);
			}
		}
	}
}
