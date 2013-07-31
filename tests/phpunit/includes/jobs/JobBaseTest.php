<?php

namespace SMW\Test;

use SMW\JobBase;

/**
 * Tests for the JobBase class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\JobBase
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class JobBaseTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\JobBase';
	}

	/**
	 * Helper method that returns a JobBase object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return JobBase
	 */
	private function getInstance() {
		return $this->getMockForAbstractClass( $this->getClass(), array( $this->newTitle(), array() ) );
	}

	/**
	 * @test JobBase::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test JobBase::getStore
	 * @test JobBase::setStore
	 *
	 * @since 1.9
	 */
	public function testGetSetStore() {

		$instance  = $this->getInstance();
		$mockStore = $this->newMockObject()->getMockStore();

		$this->assertInstanceOf( '\SMW\Store', $instance->getStore() );
		$this->assertInstanceOf( $this->getClass(), $instance->setStore( $mockStore ) );
		$this->assertInstanceOf( '\SMW\Store', $instance->getStore() );
		$this->assertEquals( $mockStore, $instance->getStore() );
	}

	/**
	 * @test JobBase::setSettings
	 * @test JobBase::getSettings
	 *
	 * @since 1.9
	 */
	public function testGetSetSettings() {

		$instance = $this->getInstance();
		$settings = $this->getSettings( array( 'test' => 'lula' ) );

		$this->assertInstanceOf( '\SMW\Settings', $instance->getSettings() );
		$this->assertInstanceOf( $this->getClass(), $instance->setSettings( $settings ) );
		$this->assertInstanceOf( '\SMW\Settings', $instance->getSettings() );
		$this->assertEquals( $settings, $instance->getSettings() );

	}

	/**
	 * @test JobBase::getCache
	 *
	 * @since 1.9
	 */
	public function testGetCache() {

		$instance = $this->getInstance();
		$this->assertInstanceOf( '\SMW\CacheHandler', $instance->getCache() );
	}

}
