<?php

namespace SMW\Test;

use SMW\HashIdGenerator;

/**
 * Tests for the HashIdGenerator class
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
 * @covers \SMW\HashIdGenerator
 *
 * @ingroup SMW
 *
 * @group SMW
 * @group SMWExtension
 */
class HashIdGeneratorTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\HashIdGenerator';
	}

	/**
	 * Helper method that returns a HashIdGenerator object
	 *
	 * @return HashIdGenerator
	 */
	private function getInstance( $hashable = null, $prefix = null ) {
		return new HashIdGenerator( $hashable, $prefix );
	}

	/**
	 * @test HashIdGenerator::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test HashIdGenerator::getPrefix
	 *
	 * @since 1.9
	 */
	public function testGetPrefix() {

		$instance = $this->getInstance( null, null );
		$this->assertNull( $instance->getPrefix() );

		$prefix   = $this->getRandomString();
		$instance = $this->getInstance( null, $prefix );
		$this->assertEquals( $prefix, $instance->getPrefix() );

	}

	/**
	 * @test HashIdGenerator::generateId
	 *
	 * @since 1.9
	 */
	public function testGenerateId() {

		$hashable = $this->getRandomString();
		$prefix   = $this->getRandomString();

		$instance = $this->getInstance( $hashable, null );
		$this->assertInternalType( 'string', $instance->generateId() );

		$instance = $this->getInstance( $hashable, $prefix );
		$this->assertInternalType( 'string', $instance->generateId() );
		$this->assertContains( $prefix, $instance->generateId() );

	}

}
