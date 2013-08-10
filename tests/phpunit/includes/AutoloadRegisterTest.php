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
 * @covers spl_autoload_register
 *
 * @ingroup SMW
 *
 * @group SMW
 * @group SMWExtension
 */
class AutoloadRegisterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @since 1.9
	 */
	public function testAutoloadedClasses() {

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
