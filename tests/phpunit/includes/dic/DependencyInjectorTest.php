<?php

namespace SMW\Test;

use SMW\DependencyInjector;

/**
 * Tests for the DependencyInjector
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\DependencyInjector
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class DependencyInjectorTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\DependencyInjector';
	}

	/**
	 * Helper method that returns a DependencyBuilder object
	 *
	 * @since 1.9
	 *
	 * @return DependencyBuilder
	 */
	private function newMockDependencyBuilder() {

		$builder = $this->getMockBuilder( '\SMW\DependencyBuilder' )
			->disableOriginalConstructor()
			->setMethods( array( 'newObject', 'getContainer', 'getArgument', 'addArgument' ) )
			->getMock();

		return $builder;
	}

	/**
	 * Helper method that returns a DependencyInjector object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return DependencyInjector
	 */
	private function getInstance() {
		return $this->getMockForAbstractClass( $this->getClass() );
	}

	/**
	 * @test DependencyInjector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test DependencyInjector::newObject
	 *
	 * @since 1.9
	 */
	public function testSetGet() {

		$instance = $this->getInstance();
		$builder  = $this->newMockDependencyBuilder();

		$instance->setDependencyBuilder( $builder );
		$this->assertEquals( $builder, $instance->getDependencyBuilder() );

	}

}
