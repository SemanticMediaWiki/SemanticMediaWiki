<?php

namespace SMW\Test;

use SMW\StoreFactory;
use SMW\Settings;

/**
 * Tests for the StoreFactory class
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
 * @since 1.9
 *
 * @file
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the StoreFactory class
 * @covers \SMW\StoreFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class StoreFactoryTest extends SemanticMediaWikiTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\StoreFactory';
	}

	/**
	 * @test StoreFactory::getStore
	 *
	 * @since 1.9
	 */
	public function testGetStore() {
		$settings = Settings::newFromGlobals();

		// Default is handled by the method itself
		$instance = StoreFactory::getStore();
		$this->assertInstanceOf( $settings->get( 'smwgDefaultStore' ), $instance );

		// Static instance
		$this->assertTrue( StoreFactory::getStore() === $instance );

		// Inject default store
		$defaulStore = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::getStore( $defaulStore );
		$this->assertInstanceOf( $defaulStore, $instance );

	}

	/**
	 * @test StoreFactory::newInstance
	 *
	 * @since 1.9
	 */
	public function testNewInstance() {
		$settings = Settings::newFromGlobals();

		// Circumvent the static instance
		$defaulStore = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::newInstance( $defaulStore );
		$this->assertInstanceOf( $defaulStore, $instance );

		// Non-static instance
		$this->assertTrue( StoreFactory::newInstance( $defaulStore ) !== $instance );

	}

	/**
	 * @test StoreFactory::newInstance
	 *
	 * @since 1.9
	 */
	public function testStoreInstanceException() {
		$this->setExpectedException( '\SMW\StoreInstanceException' );
		$instance = StoreFactory::newInstance( $this->getClass() );
	}

	/**
	 * @test smwfGetStore
	 *
	 * smwfGetStore is deprecated but due to its dependency do a quick check here
	 *
	 * FIXME Delete this test in 1.11
	 *
	 * @since 1.9
	 */
	public function testSmwfGetStore() {
		$store = smwfGetStore();
		$this->assertInstanceOf( 'SMWStore', $store );
		$this->assertInstanceOf( 'SMW\Store', $store );
	}
}
