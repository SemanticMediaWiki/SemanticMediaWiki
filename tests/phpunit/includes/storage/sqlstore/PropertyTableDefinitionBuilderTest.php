<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\PropertyTableDefinitionBuilder;

use SMWDataItem;

/**
 * @covers \SMW\SQLStore\PropertyTableDefinitionBuilder
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTableDefinitionBuilderTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\PropertyTableDefinitionBuilder';
	}

	/**
	 * @since 1.9
	 *
	 * @return PropertyTableDefinitionBuilder
	 */
	private function newInstance( $dataItems = array(), $specials = array(), $fixed = array() ) {
		return new PropertyTableDefinitionBuilder( $dataItems, $specials, $fixed );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testDITypes() {

		$test = array( SMWDataItem::TYPE_NUMBER => 'smw_di_number' );

		$instance = $this->newInstance( $test );
		$instance->runBuilder();

		$definition = $instance->getDefinition( SMWDataItem::TYPE_NUMBER, 'smw_di_number' );
		$expected = array( 'smw_di_number' => $definition );

		$this->assertEquals( $expected, $instance->getTableDefinitions() );

	}

	/**
	 * @since 1.9
	 */
	public function testFixedProperties() {

		$propertyKey = $this->newRandomString();
		$test = array( $propertyKey => SMWDataItem::TYPE_NUMBER );

		$instance = $this->newInstance( array(), array(), $test );
		$instance->runBuilder();

		$tableName = $instance->getTablePrefix() . '_' . md5( $propertyKey );
		$definition = $instance->getDefinition( SMWDataItem::TYPE_NUMBER, $tableName, $propertyKey );
		$expected = array(
			'definition' => array( $tableName => $definition ),
			'tableId' => array( $propertyKey => $tableName, '_SKEY' => null )
		);

		$this->assertEquals( $expected['definition'], $instance->getTableDefinitions() );
		$this->assertEquals( $expected['tableId'], $instance->getTableIds() );

	}

	/**
	 * @since 1.9
	 */
	public function testSpecialProperties() {

		$propertyKey = '_MDAT';
		$test = array( $propertyKey );

		$instance = $this->newInstance( array(), $test, array() );
		$instance->runBuilder();

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->getDefinition( SMWDataItem::TYPE_TIME, $tableName, $propertyKey );
		$expected = array( $tableName => $definition );

		$this->assertEquals( $expected, $instance->getTableDefinitions() );

	}

	/**
	 * @since 1.9
	 */
	public function testRedirects() {

		$propertyKey = '_REDI';
		$test = array( $propertyKey );

		$instance = $this->newInstance( array(), $test, array() );
		$instance->runBuilder();

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->getDefinition( SMWDataItem::TYPE_WIKIPAGE, $tableName, $propertyKey );
		$expected = array( $tableName => $definition );
		$tableDefinitions = $instance->getTableDefinitions();

		$this->assertFalse( $tableDefinitions[$tableName]->usesIdSubject() );

	}
}
