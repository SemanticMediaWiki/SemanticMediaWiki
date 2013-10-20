<?php

namespace SMW\Test;

use SMW\EmptyContext;

/**
 * @covers \SMW\EmptyContext
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class EmptyContextTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\EmptyContext';
	}

	/**
	 * @since 1.9
	 *
	 * @return EmptyContext
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
