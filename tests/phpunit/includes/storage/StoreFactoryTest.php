<?php

namespace SMW\Tests;

use SMW\StoreFactory;
use SMW\Settings;

/**
 * @covers \SMW\StoreFactory
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreFactoryTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		StoreFactory::clear();

		parent::tearDown();
	}

	public function testGetDefaultStore() {

		$instance = StoreFactory::getStore();

		$this->assertInstanceOf(
			Settings::newFromGlobals()->get( 'smwgDefaultStore' ),
			$instance
		);

		$this->assertSame(
			StoreFactory::getStore(),
			$instance
		);

		StoreFactory::clear();

		$this->assertNotSame(
			StoreFactory::getStore(),
			$instance
		);
	}

	public function testDifferentStoreIdInstanceInvocation() {

		$this->assertInstanceOf( 'SMW\Store', StoreFactory::getStore( '\SMWSQLStore3' ) );
		$this->assertInstanceOf( 'SMW\Store', StoreFactory::getStore( '\SMWSparqlStore' ) );

		$this->assertNotSame(
			StoreFactory::getStore( '\SMWSQLStore3' ),
			StoreFactory::getStore( '\SMWSparqlStore' )
		);
	}

	public function testSetDefaultStoreForUnitTest() {

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		StoreFactory::setDefaultStoreForUnitTest( $store );

		$this->assertSame(
			$store,
			StoreFactory::getStore()
		);
	}

	public function testStoreInstanceException() {
		$this->setExpectedException( '\SMW\InvalidStoreException' );
		StoreFactory::getStore( '\SMW\StoreFactory' );
	}

	public function testStoreWithInvalidClassThrowsException() {
		$this->setExpectedException( 'RuntimeException' );
		StoreFactory::getStore( 'foo' );
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
