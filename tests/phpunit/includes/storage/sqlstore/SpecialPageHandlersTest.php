<?php

namespace SMW\Test\SQLStore;

use SMW\Store;
use SMW\Test\SemanticMediaWikiTestCase;
use SMWSQLStore3SpecialPageHandlers;

/**
 * Tests for the SMWSQLStore3SpecialPageHandlers class
 *
 * @since 1.9
 *
 * @file
 * @ingroup Store
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMWSQLStore3SpecialPageHandlers class
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SpecialPageHandlersTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWSQLStore3SpecialPageHandlers';
	}

	/**
	 * Helper method that returns a Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	private function getStore() {

		// FIXME Use StoreFactory::getStore()
		return smwfGetStore();
	}

	/**
	 * Helper method that returns a SMWSQLStore3SpecialPageHandlers object
	 *
	 * @param Store $store
	 *
	 * @return SMWSQLStore3SpecialPageHandlers
	 */
	private function getInstance( $store ) {
		return new SMWSQLStore3SpecialPageHandlers( $store );
	}

	/**
	 * @test SMWSQLStore3SpecialPageHandlers::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getStore() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SMWSQLStore3SpecialPageHandlers::getStatistics
	 *
	 * @since 1.9
	 */
	public function testGetStatistics() {
		$instance = $this->getInstance( $this->getStore() );
		$this->assertInternalType( 'array', $instance->getStatistics() );
	}
}
