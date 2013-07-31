<?php

namespace SMW\Test;

use SMW\HashIdGenerator;

/**
 * Tests for the HashIdGenerator class
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
