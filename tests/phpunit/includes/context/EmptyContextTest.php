<?php

namespace SMW\Test;

use SMW\EmptyContext;

/**
 * @covers \SMW\EmptyContext
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @group SMW
 * @group SMWExtension
 *
 * @author mwjames
 */
class EmptyContextTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\EmptyContext';
	}

	/**
	 * Helper method that returns a EmptyContext object
	 *
	 * @since 1.9
	 */
	private function newInstance() {
		return new EmptyContext();
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testGetStore() {
		$this->assertNull( $this->newInstance()->getStore() );
	}

	/**
	 * @since 1.9
	 */
	public function testGetSettings() {
		$this->assertNull( $this->newInstance()->getSettings() );
	}

}
