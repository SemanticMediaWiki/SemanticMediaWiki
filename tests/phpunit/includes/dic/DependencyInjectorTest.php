<?php

namespace SMW\Test;

use SMW\DependencyInjector;

/**
 * @covers \SMW\DependencyInjector
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DependencyInjectorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\DependencyInjector';
	}

	/**
	 * @since 1.9
	 *
	 * @return DependencyBuilder
	 */
	private function newMockDependencyBuilder() {

		$builder = $this->getMockBuilder( '\SMW\DependencyBuilder' )
			->disableOriginalConstructor()
			->setMethods( array( 'newObject', 'getContainer', 'getArgument', 'hasArgument', 'addArgument', 'setScope' ) )
			->getMock();

		return $builder;
	}

	/**
	 * @since 1.9
	 *
	 * @return DependencyInjector
	 */
	private function newInstance() {
		return $this->getMockForAbstractClass( $this->getClass() );
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
	public function testSetGet() {

		$instance = $this->newInstance();
		$builder  = $this->newMockDependencyBuilder();

		$instance->setDependencyBuilder( $builder );
		$this->assertEquals( $builder, $instance->getDependencyBuilder() );

	}

}
