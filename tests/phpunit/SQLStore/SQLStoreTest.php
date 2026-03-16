<?php

namespace SMW\Tests\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @covers \SMW\SQLStore\SQLStore
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SQLStoreTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = new SQLStore();

		$settings = [
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => []
		];

		foreach ( $settings as $key => $value ) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}
	}

	protected function tearDown(): void {
		$this->store->clear();
		ApplicationFactory::getInstance()->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Store::class,
			$this->store
		);

		$this->assertInstanceOf(
			SQLStore::class,
			$this->store
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testGetPropertyTables() {
		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$this->assertIsArray(

			$this->store->getPropertyTables()
		);

		foreach ( $this->store->getPropertyTables() as $tid => $propTable ) {
			$this->assertInstanceOf(
				PropertyTableDefinition::class,
				$propTable
			);
		}
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesValidCustomizableProperty() {
		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$settings = [
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [ '_MDAT' ]
		];

		foreach ( $settings as $key => $value ) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}

		$this->store->clear();

		$this->assertCount(
			$defaultPropertyTableCount + 1,
			$this->store->getPropertyTables()
		);
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithInvalidCustomizableProperty() {
		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$settings = [
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [ '_MDAT', 'Foo' ]
		];

		foreach ( $settings as $key => $value ) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}

		$this->store->clear();

		$this->assertCount(
			$defaultPropertyTableCount + 1,
			$this->store->getPropertyTables()
		);
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithValidCustomizableProperties() {
		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$settings = [
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [ '_MDAT', '_MEDIA' ]
		];

		foreach ( $settings as $key => $value ) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}

		$this->store->clear();

		$this->assertCount(
			$defaultPropertyTableCount + 2,
			$this->store->getPropertyTables()
		);
	}

	public function testGetObjectIds() {
		$this->assertIsObject(

			$this->store->getObjectIds()
		);
	}

}
