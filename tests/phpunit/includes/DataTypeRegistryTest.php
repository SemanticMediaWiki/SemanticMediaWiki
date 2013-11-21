<?php

namespace SMW\Test;

use SMW\DataTypeRegistry;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\DataTypeRegistry
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
class DataTypeRegistryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\DataTypeRegistry';
	}

	/**
	 * @since 1.9
	 */
	public function testGetInstance() {

		$instance = DataTypeRegistry::getInstance();

		$this->assertInstanceOf( $this->getClass(), $instance );

		$this->assertTrue(
			DataTypeRegistry::getInstance() === $instance,
			'Asserts that getInstance() returns a static instance'
		);

		DataTypeRegistry::clear();

		$this->assertTrue(
			DataTypeRegistry::getInstance() !== $instance,
			'Asserts that instance has been reset'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testRegisterDatatype() {

		$this->assertNull(
			DataTypeRegistry::getInstance()->getDataTypeClassById( '_foo' ),
			'Asserts that prior registration getDataTypeClassById() returns null'
		);

		DataTypeRegistry::getInstance()->registerDataType( '_foo', '\SMW\FooValue', DataItem::TYPE_NOTYPE, 'FooValue' );

		$this->assertEquals(
			'\SMW\FooValue',
			DataTypeRegistry::getInstance()->getDataTypeClassById( '_foo' ),
			'Asserts that getDataTypeClassById() returns the registered class'
		);

		$this->assertEquals(
			DataItem::TYPE_NOTYPE,
			DataTypeRegistry::getInstance()->getDataItemId( '_foo' ),
			'Asserts that getDataItemId() returns the registered DataItem type'
		);

		$this->assertEquals(
			'FooValue',
			DataTypeRegistry::getInstance()->findTypeLabel( '_foo' ),
			'Asserts that findTypeLabel() returns the registered label'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testRegisterDatatypeAlias() {

		DataTypeRegistry::getInstance()->registerDataTypeAlias( '_foo', 'FooBar' );

		$this->assertEquals(
			'_foo',
			DataTypeRegistry::getInstance()->findTypeId( 'FooBar' ),
			'Asserts that findTypeID returns the registered alias label'
		);

	}

}
