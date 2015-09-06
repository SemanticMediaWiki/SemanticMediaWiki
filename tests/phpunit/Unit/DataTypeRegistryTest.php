<?php

namespace SMW\Tests;

use SMW\DataTypeRegistry;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\DataTypeRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DataTypeRegistryTest extends \PHPUnit_Framework_TestCase {

	private $dataTypeRegistry;

	protected function setUp() {
		parent::setUp();

		$this->dataTypeRegistry = DataTypeRegistry::getInstance();
	}

	protected function tearDown() {
		$this->dataTypeRegistry->clear();

		parent::tearDown();
	}

	public function testGetInstance() {

		$this->assertInstanceOf(
			'\SMW\DataTypeRegistry',
			$this->dataTypeRegistry
		);

		$this->assertSame(
			$this->dataTypeRegistry,
			DataTypeRegistry::getInstance()
		);

		DataTypeRegistry::clear();

		$this->assertNotSame(
			$this->dataTypeRegistry,
			DataTypeRegistry::getInstance()
		);
	}

	public function testRegisterDatatype() {

		$this->assertNull(
			$this->dataTypeRegistry->getDataTypeClassById( '_foo' ),
			'Asserts that prior registration getDataTypeClassById() returns null'
		);

		$this->dataTypeRegistry
			->registerDataType( '_foo', '\SMW\Tests\FooValue', DataItem::TYPE_NOTYPE, 'FooValue' );

		$this->assertEquals(
			'\SMW\Tests\FooValue',
			$this->dataTypeRegistry->getDataTypeClassById( '_foo' ),
			'Asserts that getDataTypeClassById() returns the registered class'
		);

		$this->assertEquals(
			DataItem::TYPE_NOTYPE,
			$this->dataTypeRegistry->getDataItemId( '_foo' )
		);

		$this->assertEquals(
			'FooValue',
			$this->dataTypeRegistry->findTypeLabel( '_foo' )
		);

		$this->assertEmpty(
			$this->dataTypeRegistry->findTypeLabel( 'FooNoLabel' )
		);

		$this->assertEquals(
			DataItem::TYPE_NOTYPE,
			$this->dataTypeRegistry->getDataItemId( 'FooBar' )
		);
	}

	public function testRegisterDatatypeIdAndAlias() {

		$this->dataTypeRegistry
			->registerDataType( '_foo', '\SMW\Tests\FooValue', DataItem::TYPE_NOTYPE, 'FooValue' );

		$this->assertEmpty(
			$this->dataTypeRegistry->findTypeId( 'FooBar' )
		);

		$this->dataTypeRegistry->registerDataTypeAlias( '_foo', 'FooBar' );

		$this->assertTrue(
			$this->dataTypeRegistry->isKnownTypeId( '_foo' )
		);

		$this->assertEquals(
			'_foo',
			$this->dataTypeRegistry->findTypeId( 'FooBar' ),
			'Asserts that findTypeID returns the registered alias label'
		);
	}

	public function testGetDefaultDataItemTypeIdForValidDataItemType() {
		$this->assertInternalType(
			'string',
			$this->dataTypeRegistry->getDefaultDataItemTypeId( 1 )
		);
	}

	public function testGetDefaultDataItemTypeIdForInvalidDataItemType() {
		$this->assertNull(
			$this->dataTypeRegistry->getDefaultDataItemTypeId( 9999 )
		);
	}

	public function testFindCanonicalLabelById() {
		$this->assertSame(
			'Text',
			$this->dataTypeRegistry->findCanonicalLabelById( '_txt' )
		);
	}

	public function testTypeIdAndLabelAsLanguageIndependantInvocation() {
		$instance = new DataTypeRegistry(
			array( '_wpg' => 'Page' ),
			array( 'URI'  => '_uri' ),
			array()
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

	public function testKnownAliasAsLanguageIndependantInvocation() {

		$instance = new DataTypeRegistry(
			array(),
			array( 'URI'  => '_uri' ),
			array()
		);

		$this->assertEquals(
			array( 'URI'  => '_uri' ),
			$instance->getKnownTypeAliases(),
			'Asserts that getKnownTypeAliases returns an array'
		);
	}

	public function testExtraneousCallbackFunction() {

		$instance = new DataTypeRegistry();
		$arg = 'foo';

		$instance->registerExtraneousFunction(
			'foo',
			function ( $arg ) {
				return 'bar' . $arg;
			}
		);

		$this->assertInternalType(
			'array',
			$instance->getExtraneousFunctions()
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
			array( $inputLabel => $id ),
			array()
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $registry->findTypeId( $caseVariant ) );
		}
	}

	protected function assertRegistryFindsIdForAliases( $inputLabel, array $equivalentLabels ) {
		$id = '_wpg';

		$registry = new DataTypeRegistry(
			array( $id => $inputLabel ),
			array(),
			array()
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $registry->findTypeId( $caseVariant ) );
		}
	}

}

class FooValue {
}
