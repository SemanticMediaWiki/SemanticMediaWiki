<?php

namespace SMW\Test;

use SMW\HooksLoader;

/**
 * Tests for the HookLoader class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\HooksLoader
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class HooksLoaderTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\HooksLoader';
	}

	/**
	 * Helper method that returns a HookLoader object
	 *
	 * @since 1.9
	 *
	 * @return HookLoader
	 */
	private function getInstance() {
		return HooksLoader::register( $this->getMockForAbstractClass( '\SMW\MediaWikiHook' ) );
	}

	/**
	 * @test HooksLoader::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( '\SMW\MediaWikiHook', $this->getInstance() );
	}

}
