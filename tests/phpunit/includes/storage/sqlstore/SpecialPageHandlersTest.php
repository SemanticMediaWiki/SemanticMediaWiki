<?php

namespace SMW\Test\SQLStore;

use SMW\Store;
use SMW\Test\SemanticMediaWikiTestCase;
use SMWSQLStore3SpecialPageHandlers;

/**
 * Tests for the SMWSQLStore3SpecialPageHandlers class
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
 * @ingroup Store
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMWSQLStore3SpecialPageHandlers class
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SpecialPageHandlersTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWSQLStore3SpecialPageHandlers';
	}

	/**
	 * Helper method that returns a Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	private function getStore() {

		// FIXME Use StoreFactory::getStore()
		return smwfGetStore();
	}

	/**
	 * Helper method that returns a SMWSQLStore3SpecialPageHandlers object
	 *
	 * @param Store $store
	 *
	 * @return SMWSQLStore3SpecialPageHandlers
	 */
	private function getInstance( $store ) {
		return new SMWSQLStore3SpecialPageHandlers( $store );
	}

	/**
	 * @test SMWSQLStore3SpecialPageHandlers::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getStore() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SMWSQLStore3SpecialPageHandlers::getStatistics
	 *
	 * @since 1.9
	 */
	public function testGetStatistics() {
		$instance = $this->getInstance( $this->getStore() );
		$this->assertInternalType( 'array', $instance->getStatistics() );
	}
}
