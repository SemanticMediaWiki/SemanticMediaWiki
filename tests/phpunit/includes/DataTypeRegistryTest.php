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

	public function testGetInstance() {
		$instance = DataTypeRegistry::getInstance();

		$this->assertInstanceOf(
			'\SMW\DataTypeRegistry',
			$instance
		);

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

		$this->assertEmpty(
			DataTypeRegistry::getInstance()->findTypeLabel( 'FooNoLabel' ),
			'Asserts that findTypeLabel() returns an empty label'
		);

		$this->assertEquals(
			DataItem::TYPE_NOTYPE,
			DataTypeRegistry::getInstance()->getDataItemId( 'FooBar' ),
			'Asserts TYPE_NOTYPE is returned for non-registered type'
		);
	}

	/**
	 * @since 1.9
	 */
	public function testRegisterDatatypeIdAndAlias() {
		$this->assertEmpty(
			DataTypeRegistry::getInstance()->findTypeId( 'FooBar' ),
			'Asserts that findTypeID returns empty label'
		);

		DataTypeRegistry::getInstance()->registerDataTypeAlias( '_foo', 'FooBar' );

		$this->assertTrue(
			DataTypeRegistry::getInstance()->isKnownTypeId( '_foo' )
		);

		$this->assertEquals(
			'_foo',
			DataTypeRegistry::getInstance()->findTypeId( 'FooBar' ),
			'Asserts that findTypeID returns the registered alias label'
		);
	}

	public function testGetDefaultDataItemTypeIdForValidDataItemType() {
		$this->assertInternalType(
			'string',
			DataTypeRegistry::getInstance()->getDefaultDataItemTypeId( 1 )
		);
	}

	public function testGetDefaultDataItemTypeIdForInvalidDataItemType() {
		$this->assertNull(
			DataTypeRegistry::getInstance()->getDefaultDataItemTypeId( 9999 )
		);
	}

	public function testTypeIdAndLabelAsLanguageIndependantInvocation() {
		$instance = new DataTypeRegistry(
			array( '_wpg' => 'Page' ),
			array( 'URI'  => '_uri' )
		);

		$this->assertEquals(
			'_wpg',
			$instance->findTypeId( 'Page' ),
			'Asserts that findTypeID returns empty label'
		);

		$this->assertEquals(
			array( '_wpg' => 'Page' ),
			$instance->getKnownTypeLabels(),
			'Asserts that getKnownTypeLabels returns an array'
		);
	}

	public function testLookupByLabelIsCaseInsensitive() {
		$caseVariants = array(
			'page',
			'Page',
			'PAGE',
			'pAgE',
		);

		foreach ( $caseVariants as $caseVariant ) {
			$this->assertRegistryFindsIdForLabels( $caseVariant, $caseVariants );
			$this->assertRegistryFindsIdForAliases( $caseVariant, $caseVariants );
		}
	}

	protected function assertRegistryFindsIdForLabels( $inputLabel, array $equivalentLabels ) {
		$id = '_wpg';

		$registry = new DataTypeRegistry(
			array(),
			array( $inputLabel => $id )
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $registry->findTypeId( $caseVariant ) );
		}
	}

	protected function assertRegistryFindsIdForAliases( $inputLabel, array $equivalentLabels ) {
		$id = '_wpg';

		$registry = new DataTypeRegistry(
			array( $id => $inputLabel ),
			array()
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $registry->findTypeId( $caseVariant ) );
		}
	}

}