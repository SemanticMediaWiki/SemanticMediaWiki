<?php

namespace SMW\Test;

use SMW\UpdateObserver;

/**
 * Tests for the UpdateObserver class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\UpdateObserver
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class UpdateObserverTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\UpdateObserver';
	}

	/**
	 * Helper method that returns a UpdateObserver object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return UpdateObserver
	 */
	private function getInstance() {
		return new UpdateObserver();
	}

	/**
	 * @test UpdateObserver::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test UpdateObserver::getStore
	 * @test UpdateObserver::setStore
	 *
	 * @since 1.9
	 */
	public function testGetSetStore() {

		$instance  = $this->getInstance();
		$mockStore = $this->newMockObject()->getMockStore();

		$this->assertInstanceOf( '\SMW\Store', $instance->getStore() );
		$instance->setStore( $mockStore );
		$this->assertInstanceOf( '\SMW\Store', $instance->getStore() );
		$this->assertEquals( $mockStore, $instance->getStore() );

	}

	/**
	 * @test UpdateObserver::getCache
	 *
	 * @since 1.9
	 */
	public function testGetCache() {

		$instance = $this->getInstance();
		$this->assertInstanceOf( '\SMW\CacheHandler', $instance->getCache() );
	}

	/**
	 * @test UpdateObserver::setSettings
	 * @test UpdateObserver::getSettings
	 * @dataProvider updateDispatcherDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetSetSettings( $setup ) {

		$instance = $this->getInstance();
		$settings = $this->newSettings( $setup['settings'] );

		$this->assertInstanceOf( '\SMW\Settings', $instance->getSettings() );

		$instance->setSettings( $settings );
		$this->assertEquals( $settings , $instance->getSettings() );

	}

	/**
	 * @test UpdateObserver::runUpdateDispatcher
	 * @dataProvider updateDispatcherDataProvider
	 *
	 * @since 1.9
	 */
	public function testUpdateDispatcherJob( $setup, $expected ) {

		$instance = $this->getInstance();
		$instance->setSettings( $this->newSettings( $setup['settings'] ) );

		$this->assertTrue( $instance->runUpdateDispatcher( $setup['title'] ) );
	}

	/**
	 * @test UpdateObserver::runUpdateDispatcher
	 * @dataProvider storeUpdaterDataProvider
	 *
	 * @since 1.9
	 */
	public function testStoreUpdater( $setup, $expected ) {

		$instance = $this->getInstance();
		$instance->setSettings( $this->newSettings( $setup['settings'] ) );

		$this->assertTrue( $instance->runStoreUpdater( $setup['parserData'] ) );
	}

	/**
	 * @return array
	 */
	public function storeUpdaterDataProvider() {

		$subject  = $this->newSubject();
		$mockData = $this->newMockObject( array(
			'getSubject' => $subject
		) )->getMockSemanticData();

		$parserData = $this->newMockObject( array(
			'getData'    => $mockData,
		) )->getMockParserData();

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'settings'   => array(
					'smwgEnableUpdateJobs'            => false,
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
				),
				'parserData' => $parserData
			),
			array()
		);

		return $provider;

	}

	/**
	 * @note smwgEnableUpdateJobs is set false to avoid having a Job being
	 * pushed into the "real" JobQueue
	 *
	 * @return array
	 */
	public function updateDispatcherDataProvider() {

		$title = $this->newMockObject( array(
			'getTitle' => $this->newTitle()
		) )->getMockTitleAccess();

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => false
				),
				'title' => $title
			),
			array()
		);

		// #1
		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => true
				),
				'title' => $title
			),
			array()
		);

		// #2
		$title = $this->newMockObject( array(
			'getTitle' => $this->newTitle( SMW_NS_PROPERTY )
		) )->getMockTitleAccess();

		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => true
				),
				'title' => $title
			),
			array()
		);

		// #3
		$title = $this->newMockObject( array(
			'getTitle' => $this->newTitle( SMW_NS_PROPERTY )
		) )->getMockTitleAccess();

		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => false
				),
				'title' => $title
			),
			array()
		);

		return $provider;
	}

}
