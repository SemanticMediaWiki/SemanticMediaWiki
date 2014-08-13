<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\PropertyTableDefinitionBuilder;

use SMWDataItem as DataItem;

/**
 * @covers \SMW\SQLStore\PropertyTableDefinitionBuilder
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group StoreTest
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTableDefinitionBuilderTest extends \PHPUnit_Framework_TestCase {

	protected $hooks = array();

	protected function setUp() {
		parent::setUp();

		if ( isset( $GLOBALS['wgHooks']['SMW::SQLStore::updatePropertyTableDefinitions'] ) ) {
			$this->hooks = $GLOBALS['wgHooks']['SMW::SQLStore::updatePropertyTableDefinitions'];
			$GLOBALS['wgHooks']['SMW::SQLStore::updatePropertyTableDefinitions'] = array();
		}
	}

	protected function tearDown() {

		if ( $this->hooks !== array() ) {
			$GLOBALS['wgHooks']['SMW::SQLStore::updatePropertyTableDefinitions'] = $this->hooks;
		}

		parent::tearDown();
	}

	public function getClass() {
		return '\SMW\SQLStore\PropertyTableDefinitionBuilder';
	}

	/**
	 * @return PropertyTableDefinitionBuilder
	 */
	private function acquireInstance( $dataItems = array(), $specials = array(), $fixed = array() ) {
		return new PropertyTableDefinitionBuilder( $dataItems, $specials, $fixed );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->acquireInstance() );
	}

	public function testDataItemTypes() {

		$parameters = array( DataItem::TYPE_NUMBER => 'smw_di_number' );

		$instance = $this->acquireInstance( $parameters );
		$instance->runBuilder();

		$definition = $instance->getDefinition( DataItem::TYPE_NUMBER, 'smw_di_number' );

		$expected = array(
			'smw_di_number' => $definition
		);

		$this->assertEquals( $expected, $instance->getTableDefinitions() );
	}

	public function testFixedProperties() {

		$propertyKey = 'Foo';
		$parameters = array( $propertyKey => DataItem::TYPE_NUMBER );

		$instance = $this->acquireInstance( array(), array(), $parameters );
		$instance->runBuilder();

		$tableName = $instance->getTablePrefix() . '_' . md5( $propertyKey );
		$definition = $instance->getDefinition( DataItem::TYPE_NUMBER, $tableName, $propertyKey );

		$expected = array(
			'definition' => array( $tableName => $definition ),
			'tableId' => array( $propertyKey => $tableName, '_SKEY' => null )
		);

		$this->assertEquals( $expected['definition'], $instance->getTableDefinitions() );
		$this->assertEquals( $expected['tableId'], $instance->getTableIds() );
	}

	public function testSpecialProperties() {

		$propertyKey = '_MDAT';
		$parameters = array( $propertyKey );

		$instance = $this->acquireInstance( array(), $parameters, array() );
		$instance->runBuilder();

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->getDefinition( DataItem::TYPE_TIME, $tableName, $propertyKey );
		$expected = array( $tableName => $definition );

		$this->assertEquals( $expected, $instance->getTableDefinitions() );
	}

	public function testRedirects() {

		$propertyKey = '_REDI';
		$parameters = array( $propertyKey );

		$instance = $this->acquireInstance( array(), $parameters, array() );
		$instance->runBuilder();

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->getDefinition( DataItem::TYPE_WIKIPAGE, $tableName, $propertyKey );
		$expected = array( $tableName => $definition );
		$tableDefinitions = $instance->getTableDefinitions();

		$this->assertFalse( $tableDefinitions[$tableName]->usesIdSubject() );
	}

}
