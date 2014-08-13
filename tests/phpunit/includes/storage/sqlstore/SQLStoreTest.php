<?php

namespace SMW\Test\SQLStore;

use SMWSQLStore3;
use SMW\Settings;

/**
 * @covers \SMWSQLStore3
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
class SQLStoreTest extends \PHPUnit_Framework_TestCase {

	/** @var array */
	protected $defaultPropertyTableCount = 0;

	public function getClass() {
		return '\SMWSQLStore3';
	}

	private function acquireInstance() {
		$instance = new SMWSQLStore3();

		$instance->setConfiguration( Settings::newFromArray( array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array()
		) ) );

		$this->defaultPropertyTableCount = count( $instance->getPropertyTables() );
		$instance->clear();

		return $instance;
	}

	public function testCanConstruct() {
		$instance = $this->acquireInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testGetPropertyTables() {

		$instance = $this->acquireInstance();

		$instance->setConfiguration( Settings::newFromArray( array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array()
		) ) );

		$this->assertInternalType( 'array', $instance->getPropertyTables() );

		foreach ( $instance->getPropertyTables() as $tid => $propTable ) {
			$this->assertInstanceOf( '\SMW\SQLStore\TableDefinition', $propTable );
		}
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesValidCustomizableProperty() {

		$instance = $this->acquireInstance();

		$instance->setConfiguration( Settings::newFromArray( array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array( '_MDAT' )
		) ) );

		$this->assertCount( $this->defaultPropertyTableCount + 1, $instance->getPropertyTables() );
		$instance->clear();
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithInvalidCustomizableProperty() {

		$instance = $this->acquireInstance();

		$instance->setConfiguration( Settings::newFromArray( array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array( '_MDAT', 'Foo' )
		) ) );

		$this->assertCount( $this->defaultPropertyTableCount + 1, $instance->getPropertyTables() );
		$instance->clear();
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithValidCustomizableProperties() {

		$instance = $this->acquireInstance();

		$instance->setConfiguration( Settings::newFromArray( array(
			'smwgFixedProperties' => array(),
			'smwgPageSpecialProperties' => array( '_MDAT', '_MEDIA' )
		) ) );

		$this->assertCount( $this->defaultPropertyTableCount + 2, $instance->getPropertyTables() );
		$instance->clear();
	}

	public function testGetStatisticsTable() {
		$this->assertInternalType( 'string', $this->acquireInstance()->getStatisticsTable() );
	}

	public function testGetObjectIds() {
		$this->assertInternalType( 'object', $this->acquireInstance()->getObjectIds() );
		$this->assertInternalType( 'string', $this->acquireInstance()->getObjectIds()->getIdTable() );
	}

}
