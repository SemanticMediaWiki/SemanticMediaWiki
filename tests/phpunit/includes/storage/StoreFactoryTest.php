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
class StoreFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testGetStore() {

		$settings = Settings::newFromGlobals();

		// Default is handled by the method itself
		$instance = StoreFactory::getStore();
		$this->assertInstanceOf( $settings->get( 'smwgDefaultStore' ), $instance );

		$this->assertSame(
			StoreFactory::getStore(),
			$instance
		);

		// Reset static instance
		StoreFactory::clear();

		$this->assertNotSame(
			StoreFactory::getStore(),
			$instance
		);

		// Inject default store
		$defaulStore = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::getStore( $defaulStore );
		$this->assertInstanceOf( $defaulStore, $instance );
	}

	public function testNewInstance() {

		$settings = Settings::newFromGlobals();

		$defaulStore = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::newInstance( $defaulStore );
		$this->assertInstanceOf( $defaulStore, $instance );

		$this->assertNotSame(
			StoreFactory::newInstance( $defaulStore ),
			$instance
		);
	}

	public function testStoreInstanceException() {
		$this->setExpectedException( '\SMW\InvalidStoreException' );
		StoreFactory::newInstance( '\SMW\StoreFactory' );
	}

	public function testStoreWithInvalidClassThrowsException() {
		$this->setExpectedException( 'RuntimeException' );
		StoreFactory::newInstance( 'foo' );
	}

	/**
	 * smwfGetStore is deprecated but due to its dependency do a quick check here
	 *
	 * FIXME Delete this test in 1.11
	 */
	public function testSmwfGetStore() {
		$store = smwfGetStore();
		$this->assertInstanceOf( 'SMWStore', $store );
		$this->assertInstanceOf( 'SMW\Store', $store );
	}

}
