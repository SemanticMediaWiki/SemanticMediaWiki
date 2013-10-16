<?php

namespace SMW\Test;

use SMW\BaseContext;

/**
 * @covers \SMW\BaseContext
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @group SMW
 * @group SMWExtension
 *
 * @author mwjames
 */
class BaseContextTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\BaseContext';
	}

	/**
	 * Helper method that returns a BaseContext object
	 *
	 * @since 1.9
	 */
	private function newInstance( $builder = null ) {
		return new BaseContext( $builder );
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
	public function testGetSettings() {

		$settings = $this->newSettings( array( 'Foo' => 'Bar' ) );
		$instance = $this->newInstance();
		$instance->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		$this->assertInstanceOf(
			'\SMW\Settings',
			$instance->getSettings(),
			'Asserts that getSettings() yields a Settings object'
		);

		$this->assertEquals(
			$settings,
			$instance->getSettings(),
			'Asserts that getSettings() yields an expected result'
		);

		$this->assertTrue(
			$instance->getSettings() === $instance->getDependencyBuilder()->newObject( 'Settings' ),
			"Asserts that getSettings() returns the same instance (syncronized object instance)"
		);

	}

	/**
	 * @since 1.9
	 */
	public function testGetStore() {

		$store = $this->newMockBuilder()->newObject( 'Store' );
		$instance = $this->newInstance();
		$instance->getDependencyBuilder()->getContainer()->registerObject( 'Store', $store );

		$this->assertInstanceOf(
			'\SMW\Store',
			$instance->getStore(),
			'Asserts that getStore() yields a Store object'
		);

		$this->assertEquals(
			$store,
			$instance->getStore(),
			'Asserts that getSettings() yields an expected result'
		);

		$this->assertTrue(
			$instance->getStore() === $instance->getDependencyBuilder()->newObject( 'Store' ),
			"Asserts that getStore() returns the same instance (syncronized object instance)"
		);

	}

	/**
	 * @since 1.9
	 */
	public function testSetGetDependencyBuilder() {

		$builder  = $this->newDependencyBuilder();
		$instance = $this->newInstance();

		$this->assertInstanceOf(
			'\SMW\DependencyBuilder',
			$instance->getDependencyBuilder(),
			'Asserts that getDependencyBuilder() yields a default DependencyBuilder object'
		);

		$instance = $this->newInstance( $builder );

		$this->assertTrue(
			$builder === $instance->getDependencyBuilder(),
			'Asserts that getDependencyBuilder() yields the same instance used for constructor injection'
		);

	}

}
