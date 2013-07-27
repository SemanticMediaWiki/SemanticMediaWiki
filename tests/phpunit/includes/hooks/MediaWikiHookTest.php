<?php

namespace SMW\Test;

use SMW\MediaWikiHook;

/**
 * Tests for the MediaWikiHook class
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
 * @covers \SMW\MediaWikiHook
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class MediaWikiHookTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\MediaWikiHook';
	}

	/**
	 * Helper method that returns a MediaWikiHook object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return MediaWikiHook
	 */
	private function getInstance() {
		return $this->getMockForAbstractClass( $this->getClass() );
	}

	/**
	 * @test MediaWikiHook::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test MediaWikiHook::getStore
	 * @test MediaWikiHook::setStore
	 *
	 * @since 1.9
	 */
	public function testGetSetStore() {

		$instance = $this->getInstance();

		$this->assertInstanceOf( '\SMW\Store', $instance->getStore() );
		$instance->setStore( $this->newMockObject()->getMockStore() );
		$this->assertInstanceOf( '\SMW\Store', $instance->getStore() );
	}

	/**
	 * @test MediaWikiHook::setSettings
	 * @test MediaWikiHook::getSettings
	 *
	 * @since 1.9
	 */
	public function testGetSetSettings() {

		$instance = $this->getInstance();
		$settings = $this->getSettings( array( 'test' => true ) );

		$this->assertInstanceOf( '\SMW\Settings', $instance->getSettings() );
		$instance->setSettings( $settings );
		$this->assertEquals( $settings, $instance->getSettings() );
	}

	/**
	 * @test MediaWikiHook::getCache
	 *
	 * @since 1.9
	 */
	public function testGetCache() {

		$instance = $this->getInstance();
		$this->assertInstanceOf( '\SMW\CacheHandler', $instance->getCache() );
	}

}
