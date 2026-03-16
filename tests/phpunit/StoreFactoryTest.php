<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\Exception\StoreNotFoundException;
use SMW\SPARQLStore\SPARQLStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
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
class StoreFactoryTest extends TestCase {

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
		$this->assertInstanceOf( Store::class, StoreFactory::getStore( SQLStore::class ) );
		$this->assertInstanceOf( Store::class, StoreFactory::getStore( SPARQLStore::class ) );

		$this->assertNotSame(
			StoreFactory::getStore( SQLStore::class ),
			StoreFactory::getStore( SPARQLStore::class )
		);
	}

	public function testStoreInstanceException() {
		$this->expectException( StoreNotFoundException::class );
		StoreFactory::getStore( StoreFactory::class );
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

		$this->assertInstanceOf( Store::class, $store );
	}

}
