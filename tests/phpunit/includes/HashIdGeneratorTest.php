<?php

namespace SMW\Test;

use SMW\HashIdGenerator;

/**
 * @covers \SMW\HashIdGenerator
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class HashIdGeneratorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\HashIdGenerator';
	}

	/**
	 * @return HashIdGenerator
	 */
	private function newInstance( $hashable = null, $prefix = null ) {
		return new HashIdGenerator( $hashable, $prefix );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testGetPrefix() {

		$instance = $this->newInstance( null, null );
		$this->assertNull( $instance->getPrefix() );

		$prefix   = $this->newRandomString();
		$instance = $this->newInstance( null, $prefix );
		$this->assertEquals( $prefix, $instance->getPrefix() );

		// Set prefix
		$prefix   = $this->newRandomString();
		$instance = $this->newInstance( null, null );
		$this->assertEquals( $prefix, $instance->setPrefix( $prefix )->getPrefix() );

	}

	/**
	 * @since 1.9
	 */
	public function testGenerateId() {

		$hashable = $this->newRandomString();
		$prefix   = $this->newRandomString();

		$instance = $this->newInstance( $hashable, null );
		$this->assertInternalType( 'string', $instance->generateId() );

		$instance = $this->newInstance( $hashable, $prefix );
		$this->assertInternalType( 'string', $instance->generateId() );
		$this->assertContains( $prefix, $instance->generateId() );

	}

}
