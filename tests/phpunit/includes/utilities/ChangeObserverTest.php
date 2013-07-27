<?php

namespace SMW\Test;

use SMW\ChangeObserver;

/**
 * Tests for the ChangeObserver class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ChangeObserver
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ChangeObserverTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ChangeObserver';
	}

	/**
	 * Helper method that returns a ChangeObserver object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return ChangeObserver
	 */
	private function getInstance() {
		return new ChangeObserver();
	}

	/**
	 * @test ChangeObserver::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ChangeObserver::getStore
	 * @test ChangeObserver::setStore
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
	 * @test ChangeObserver::getCache
	 *
	 * @since 1.9
	 */
	public function testGetCache() {

		$instance = $this->getInstance();
		$this->assertInstanceOf( '\SMW\CacheHandler', $instance->getCache() );
	}

	/**
	 * @test ChangeObserver::setSettings
	 * @test ChangeObserver::getSettings
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetSetSettings( $setup ) {

		$instance = $this->getInstance();
		$settings = $this->getSettings( $setup['settings'] );

		$this->assertInstanceOf( '\SMW\Settings', $instance->getSettings() );

		$instance->setSettings( $settings ) ;
		$this->assertEquals( $settings , $instance->getSettings() );

	}

	/**
	 * @test ChangeObserver::runUpdateDispatcher
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testUpdateDispatcherJob( $setup, $expected ) {

		$instance = $this->getInstance();
		$instance->setSettings( $this->getSettings( $setup['settings'] ) );

		$this->assertTrue( $instance->runUpdateDispatcher( $setup['title'] ) );
	}

	/**
	 * @note smwgEnableUpdateJobs is set false to avoid having a Job being
	 * pushed into the "real" JobQueue
	 *
	 * @return array
	 */
	public function titleDataProvider() {

		$title = $this->getMockForAbstractClass( '\SMW\TitleProvider' );

		$title->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->newTitle() ) );

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs' => false,
					'smwgDeferredPropertyUpdate' => false
				),
				'title'    => $title
			),
			array()
		);

		// #1
		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs' => false,
					'smwgDeferredPropertyUpdate' => true
				),
				'title'    => $title
			),
			array()
		);

		return $provider;
	}

}
