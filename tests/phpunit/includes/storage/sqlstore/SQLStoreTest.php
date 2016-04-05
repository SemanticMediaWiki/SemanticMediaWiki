<?php

namespace SMW\Test\SQLStore;

use SMW\ApplicationFactory;
use SMWSQLStore3;

/**
 * @covers \SMWSQLStore3
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SQLStoreTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = new SMWSQLStore3();

		$settings = array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array()
		);

		foreach ( $settings as $key => $value ) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}
	}

	protected function tearDown() {
		$this->store->clear();
		ApplicationFactory::getInstance()->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWSQLStore3',
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\SQLStore',
			$this->store
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testGetPropertyTables() {

		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$this->assertInternalType(
			'array',
			$this->store->getPropertyTables()
		);

		foreach ( $this->store->getPropertyTables() as $tid => $propTable ) {
			$this->assertInstanceOf(
				'\SMW\SQLStore\TableDefinition',
				$propTable
			);
		}
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesValidCustomizableProperty() {

		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$settings = array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array( '_MDAT' )
		);

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

		$settings = array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array( '_MDAT', 'Foo' )
		);

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

		$settings = array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array( '_MDAT', '_MEDIA' )
		);

		foreach ( $settings as $key => $value ) {
			ApplicationFactory::getInstance()->getSettings()->set( $key, $value );
		}

		$this->store->clear();

		$this->assertCount(
			$defaultPropertyTableCount + 2,
			$this->store->getPropertyTables()
		);
	}

	public function testGetStatisticsTable() {

		$this->assertInternalType(
			'string',
			$this->store->getStatisticsTable()
		);
	}

	public function testGetObjectIds() {

		$this->assertInternalType(
			'object',
			$this->store->getObjectIds()
		);

		$this->assertInternalType(
			'string',
			$this->store->getObjectIds()->getIdTable()
		);
	}

}
