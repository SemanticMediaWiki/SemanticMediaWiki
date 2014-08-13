<?php

namespace SMW\Test;

use SMW\CacheIdGenerator;

/**
 * @covers \SMW\CacheIdGenerator
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
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CacheIdGenerator';
	}

	/**
	 * @since 1.9
	 *
	 * @return CacheIdGenerator
	 */
	private function newInstance( $hashable = null, $prefix = null ) {
		return new CacheIdGenerator( $hashable, $prefix );
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
		$this->assertInternalType( 'string', $instance->getPrefix() );
		$this->assertContains( 'smw-test:smw', $instance->getPrefix() );

		$prefix   = $this->newRandomString();
		$instance = $this->newInstance( null, $prefix );

		$this->assertInternalType( 'string', $instance->getPrefix() );
		$this->assertContains( 'smw-test:smw:' . $prefix, $instance->getPrefix() );

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
