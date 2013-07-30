<?php

namespace SMW\Test;

use SMW\CacheIdGenerator;

/**
 * Tests for the CacheIdGenerator class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\CacheIdGenerator
 *
 * @ingroup SMW
 *
 * @group SMW
 * @group SMWExtension
 */
class CacheIdGeneratorTest extends SemanticMediaWikiTestCase {

	/**
	 * Holds original values of MediaWiki configuration settings
	 * @var array
	 */
	private $mwGlobals = array();

	/** Set-up */
	protected function setUp() {
		parent::setUp();

		$this->mwGlobals['wgCachePrefix'] = $GLOBALS['wgCachePrefix'];
		$GLOBALS['wgCachePrefix'] = 'smw-test';
	}

	/** Tear down */
	protected function tearDown() {
		$GLOBALS['wgCachePrefix'] = $this->mwGlobals['wgCachePrefix'];
		$this->mwGlobals = array();

		parent::tearDown();
	}

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CacheIdGenerator';
	}

	/**
	 * Helper method that returns a CacheIdGenerator object
	 *
	 * @return CacheIdGenerator
	 */
	private function getInstance( $hashable = null, $prefix = null ) {
		return new CacheIdGenerator( $hashable, $prefix );
	}

	/**
	 * @test CacheIdGenerator::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test CacheIdGenerator::getPrefix
	 *
	 * @since 1.9
	 */
	public function testGetPrefix() {

		$instance = $this->getInstance( null, null );
		$this->assertInternalType( 'string', $instance->getPrefix() );
		$this->assertContains( 'smw-test:smw', $instance->getPrefix() );

		$prefix   = $this->getRandomString();
		$instance = $this->getInstance( null, $prefix );

		$this->assertInternalType( 'string', $instance->getPrefix() );
		$this->assertContains( 'smw-test:smw:' . $prefix, $instance->getPrefix() );

	}

	/**
	 * @test CacheIdGenerator::generateId
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
