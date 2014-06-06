<?php

namespace SMW\Test\SQLStore;

use SMW\Store\StoreConfig;
use SMWSQLStore3;

/**
 * @covers \SMWSQLStore3
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
class SQLStoreTest extends \PHPUnit_Framework_TestCase {

	/** @var array */
	protected $defaultPropertyTableCount = 0;

	/** @var StoreConfig */
	protected $instance = null;

	protected function setUp() {
		parent::setUp();

		$this->instance = new SMWSQLStore3();

		$storeConfig = new StoreConfig();
		$storeConfig->set( 'smwgFixedProperties', array() );
		$storeConfig->set( 'smwgPageSpecialProperties', array() );

		$this->instance->setConfiguration( $storeConfig );

		$this->defaultPropertyTableCount = count( $this->instance->getPropertyTables() );
		$this->instance->clear();
	}

	protected function tearDown() {
		$this->instance->clear();

		parent::tearDown();
	}


	public function testCanConstruct() {
		$this->assertInstanceOf( '\SMWSQLStore3', $this->instance );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testGetPropertyTables() {

		$this->assertInternalType( 'array', $this->instance->getPropertyTables() );

		foreach ( $this->instance->getPropertyTables() as $tid => $propTable ) {
			$this->assertInstanceOf( '\SMW\SQLStore\TableDefinition', $propTable );
		}
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesValidCustomizableProperty() {

		$this->instance->getConfiguration()->set( 'smwgPageSpecialProperties', array( '_MDAT' ) );

		$this->assertCount( $this->defaultPropertyTableCount + 1, $this->instance->getPropertyTables() );
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithInvalidCustomizableProperty() {

		$this->instance->getConfiguration()->set( 'smwgPageSpecialProperties', array( '_MDAT', 'Foo' ) );

		$this->assertCount( $this->defaultPropertyTableCount + 1, $this->instance->getPropertyTables() );
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithValidCustomizableProperties() {

		$this->instance->getConfiguration()->set( 'smwgPageSpecialProperties', array( '_MDAT', '_MEDIA' ) );

		$this->assertCount( $this->defaultPropertyTableCount + 2, $this->instance->getPropertyTables() );
	}

	public function testGetStatisticsTable() {
		$this->assertInternalType( 'string', $this->instance->getStatisticsTable() );
	}

	public function testGetObjectIds() {
		$this->assertInternalType( 'object', $this->instance->getObjectIds() );
		$this->assertInternalType( 'string', $this->instance->getObjectIds()->getIdTable() );
	}

}
