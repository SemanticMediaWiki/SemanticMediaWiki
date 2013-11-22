<?php

namespace SMW\Test;

use SMW\StoreFactory;
use SMW\Settings;

/**
 * @covers \SMW\StoreFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreFactoryTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\StoreFactory';
	}

	/**
	 * @since 1.9
	 */
	public function testGetStore() {

		$settings = Settings::newFromGlobals();

		// Default is handled by the method itself
		$instance = StoreFactory::getStore();
		$this->assertInstanceOf( $settings->get( 'smwgDefaultStore' ), $instance );

		// Static instance
		$this->assertTrue( StoreFactory::getStore() === $instance );

		// Reset static instance
		StoreFactory::clear();
		$this->assertTrue( StoreFactory::getStore() !== $instance );

		// Inject default store
		$defaulStore = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::getStore( $defaulStore );
		$this->assertInstanceOf( $defaulStore, $instance );

	}

	/**
	 * @since 1.9
	 */
	public function testNewInstance() {

		$settings = Settings::newFromGlobals();

		// Circumvent the static instance
		$defaulStore = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::newInstance( $defaulStore );
		$this->assertInstanceOf( $defaulStore, $instance );

		// Non-static instance
		$this->assertTrue( StoreFactory::newInstance( $defaulStore ) !== $instance );

	}

	/**
	 * @since 1.9
	 */
	public function testStoreInstanceException() {
		$this->setExpectedException( '\SMW\InvalidStoreException' );
		$instance = StoreFactory::newInstance( $this->getClass() );
	}

	/**
	 * smwfGetStore is deprecated but due to its dependency do a quick check here
	 *
	 * FIXME Delete this test in 1.11
	 *
	 * @since 1.9
	 */
	public function testSmwfGetStore() {
		$store = smwfGetStore();
		$this->assertInstanceOf( 'SMWStore', $store );
		$this->assertInstanceOf( 'SMW\Store', $store );
	}
}
