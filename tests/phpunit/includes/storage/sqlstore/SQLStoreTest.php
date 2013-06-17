<?php

namespace SMW\Test\SQLStore;

use SMWSQLStore3;

/**
 * Tests for the SMWSQLStore3 class
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
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers SMWSQLStore3
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 */
class SQLStoreTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWSQLStore3';
	}

	/**
	 * Helper method that returns a SQLStore object
	 *
	 * @since 1.9
	 *
	 * @return SQLStore
	 */
	private function getInstance() {
		return new SMWSQLStore3();
	}

	/**
	 * @test SQLStore::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SQLStore::getPropertyTables
	 *
	 * @since 1.9
	 */
	public function testGetPropertyTables() {

		$instance = $this->getInstance();
		$result = $instance->getPropertyTables();

		$this->assertInternalType( 'array', $result );

		foreach ( $result as $tid => $propTable ) {
			$this->assertInstanceOf( '\SMW\SQLStore\TableDefinition', $propTable );
		}
	}
}
