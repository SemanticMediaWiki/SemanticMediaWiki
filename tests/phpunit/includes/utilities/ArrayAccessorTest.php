<?php

namespace SMW\Test;

use SMW\ArrayAccessor;

/**
 * Tests for the ArrayAccessor class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
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
