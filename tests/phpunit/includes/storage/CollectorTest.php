<?php

namespace SMW\Test;

use SMW\ArrayAccessor;

/**
 * Tests for the Collector class
 *
 * @file
 * @license GNU GPL v2+
 * @since   1.9
 *
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

		// Non-cached scenario
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
		$this->assertNull( $instance->getCacheDate() );
		$this->assertFalse( $instance->isCached() );
		$this->assertEquals( count( $expected ), $instance->getCount() );

		// Cached scenario
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
		$this->assertNotNull( $instance->getCacheDate() );
		$this->assertTrue( $instance->isCached() );

	}
}
