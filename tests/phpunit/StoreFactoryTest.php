<?php

namespace SMW\Tests;

use SMW\StoreFactory;

/**
 * @covers \SMW\StoreFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class StoreFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	protected function tearDown(): void {
		StoreFactory::clear();
		parent::tearDown();
	}

	public function testGetDefaultStore() {
		$instance = StoreFactory::getStore();

		$this->assertInstanceOf(
			$GLOBALS['smwgDefaultStore'],
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
		$this->assertInstanceOf( 'SMW\Store', StoreFactory::getStore( '\SMW\SQLStore\SQLStore' ) );
		$this->assertInstanceOf( 'SMW\Store', StoreFactory::getStore( '\SMW\SPARQLStore\SPARQLStore' ) );

		$this->assertNotSame(
			StoreFactory::getStore( '\SMW\SQLStore\SQLStore' ),
			StoreFactory::getStore( '\SMW\SPARQLStore\SPARQLStore' )
		);
	}

	public function testStoreInstanceException() {
		$this->expectException( '\SMW\Exception\StoreNotFoundException' );
		StoreFactory::getStore( '\SMW\StoreFactory' );
	}

	public function testStoreWithInvalidClassThrowsException() {
		$this->expectException( 'RuntimeException' );
		StoreFactory::getStore( 'foo' );
	}

	/**
	 * smwfGetStore is deprecated but due to its dependency do a quick check here
	 *
	 * FIXME Delete this test in 1.11
	 */
	public function testSmwfGetStore() {
		$store = smwfGetStore();

		$this->assertInstanceOf( 'SMW\Store', $store );
	}

}
