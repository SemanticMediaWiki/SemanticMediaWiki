<?php

namespace SMW\Test;

use SMW\NullDependencyContainer;

/**
 * @covers \SMW\NullDependencyContainer
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class NullDependencyContainerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\NullDependencyContainer';
	}

	/**
	 * @since 1.9.0.2
	 */
	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), new NullDependencyContainer );
	}

	/**
	 * @since 1.9.0.2
	 */
	public function testLoadAllDefinitions() {

		$instance = new NullDependencyContainer;
		$this->assertEquals( null, $instance->loadAllDefinitions() );

	}

}
