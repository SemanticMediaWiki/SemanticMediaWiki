<?php

namespace SMW\Test;

use SMW\ArrayAccessor;

/**
 * Tests for the Collector class
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
 * @covers \SMW\Store\Collector
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class CollectorTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Store\Collector';
	}

	/**
	 * Helper method that returns a Collector object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return Collector
	 */
	private function getInstance( $doCollect = array(), $cacheAccessor = array() ) {

		$collector = $this->getMockBuilder( $this->getClass() )
			->setMethods( array( 'cacheAccessor', 'doCollect' ) )
			->getMock();

		$collector->expects( $this->any() )
			->method( 'doCollect' )
			->will( $this->returnValue( $doCollect ) );

		$collector->expects( $this->any() )
			->method( 'cacheAccessor' )
			->will( $this->returnValue( new ArrayAccessor( $cacheAccessor ) ) );

		return $collector;
	}

	/**
	 * @test Collector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test Collector::getResults
	 *
	 * @since 1.9
	 */
	public function testGetResults() {

		$accessor = array(
			'id'      => rand(),
			'type'    => false,
			'enabled' => false,
			'expiry'  => 100
		);

		$expected = array( $this->getRandomString() );

		$instance = $this->getInstance( $expected, $accessor );
		$result   = $instance->getResults();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );

		$accessor = array(
			'id'      => rand(),
			'type'    => 'hash',
			'enabled' => true,
			'expiry'  => 100
		);

		$expected = array( $this->getRandomString(), $this->getRandomString() );

		$instance = $this->getInstance( $expected, $accessor );
		$result   = $instance->getResults();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );

		// Initialized with a different 'expected' set but due to that the id
		// has not changed results are expected to be cached and be equal to
		// the results of the previous initialization
		$instance = $this->getInstance( array( 'Lula' ), $accessor );
		$this->assertEquals( $result, $instance->getResults() );

	}
}
