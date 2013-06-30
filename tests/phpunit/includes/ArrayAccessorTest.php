<?php

namespace SMW\Test;

use SMW\ArrayAccessor;

/**
 * Tests for the ArrayAccessor class
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
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\ArrayAccessor
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ArrayAccessorTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ArrayAccessor';
	}

	/**
	 * Helper method that returns a ArrayAccessor object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return ArrayAccessor
	 */
	private function getInstance( array $setup = array() ) {
		return new ArrayAccessor( $setup );
	}

	/**
	 * @test ArrayAccessor::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ArrayAccessor::get
	 *
	 * @since 1.9
	 */
	public function testInvalidArgumentException() {

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance = $this->getInstance();
		$this->assertInternalType( 'string', $instance->get( 'lala' ) );
	}

	/**
	 * @test ArrayAccessor::get
	 * @test ArrayAccessor::set
	 * @test ArrayAccessor::toArray
	 *
	 * @since 1.9
	 */
	public function testRoundTrip() {

		$id       = $this->getRandomString();
		$expected = array( $id => array( $this->getRandomString(), $this->getRandomString() ) );
		$instance = $this->getInstance( $expected );

		// Get
		$this->assertInternalType( 'array', $instance->get( $id ) );
		$this->assertEquals( $expected, $instance->toArray() );

		// Set
		$set = $this->getRandomString();
		$instance->set( $id, $set );
		$this->assertEquals( $set, $instance->get( $id ) );

	}
}
