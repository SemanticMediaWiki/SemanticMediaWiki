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

	public function testIsEqualItemType() {

		$this->assertTrue(
			$this->dataTypeRegistry->isEqualByType( '_wpg', '__sob' )
		);

		$this->assertFalse(
			$this->dataTypeRegistry->isEqualByType( '_wpg', '_txt' )
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
			$this->dataTypeRegistry->findTypeByLabel( 'FooBar' )
		);

		$this->dataTypeRegistry->registerDataTypeAlias( '_foo', 'FooBar' );

		$this->assertTrue(
			$this->dataTypeRegistry->isRegistered( '_foo' )
		);

		$this->assertEquals(
			'_foo',
			$this->dataTypeRegistry->findTypeByLabel( 'FooBar' ),
			'Asserts that findTypeByLabel returns the registered alias label'
		);
	}

	public function testGetDefaultDataItemTypeIdForValidDataItemType() {
		$this->assertInternalType(
			'string',
			$this->dataTypeRegistry->getDefaultDataItemByType( 1 )
		);
	}

	public function testGetDefaultDataItemTypeIdForInvalidDataItemType() {
		$this->assertNull(
			$this->dataTypeRegistry->getDefaultDataItemByType( 9999 )
		);
	}

	public function testFindCanonicalLabelById() {
		$this->assertSame(
			'Text',
			$this->dataTypeRegistry->findCanonicalLabelById( '_txt' )
		);
	}

	public function testTypeIdAndLabelAsLanguageIndependantInvocation() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [ '_wpg' => 'Page' ] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [ 'URI'  => '_uri' ] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		$this->assertEquals(
			'_wpg',
			$instance->findTypeByLabel( 'Page' ),
			'Asserts that findTypeByLabel returns empty label'
		);

		$this->assertEquals(
			[ '_wpg' => 'Page' ],
			$instance->getKnownTypeLabels(),
			'Asserts that getKnownTypeLabels returns an array'
		);
	}

	public function testKnownAliasAsLanguageIndependantInvocation() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [ 'URI'  => '_uri' ] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		$this->assertEquals(
			[ 'URI'  => '_uri' ],
			$instance->getKnownTypeAliases(),
			'Asserts that getKnownTypeAliases returns an array'
		);
	}

	public function testExtraneousCallbackFunction() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry( $lang );
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
		$caseVariants = [
			'page',
			'Page',
			'PAGE',
			'pAgE',
		];

		foreach ( $caseVariants as $caseVariant ) {
			$this->assertRegistryFindsIdForLabels( $caseVariant, $caseVariants );
			$this->assertRegistryFindsIdForAliases( $caseVariant, $caseVariants );
		}
	}

	public function testFindTypeByLabelAndLanguage() {

		$this->assertSame(
			'_num',
			$this->dataTypeRegistry->findTypeByLabelAndLanguage( 'Número', 'es' )
		);

		$this->assertSame(
			'_num',
			$this->dataTypeRegistry->findTypeByLabelAndLanguage( '数值型', 'zh-Hans' )
		);
	}

	public function testSubDataType() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		$instance->registerDataType( '_foo', 'FooValue', DataItem::TYPE_NOTYPE, false, true );

		$this->assertTrue(
			$instance->isSubDataType( '_foo' )
		);
	}

	public function testBrowsableType() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		$instance->registerDataType( '_foo', 'FooValue', DataItem::TYPE_NOTYPE, false, true, true );
		$instance->registerDataType( '_bar', 'BarValue', DataItem::TYPE_NOTYPE );

		$this->assertTrue(
			$instance->isBrowsableType( '_foo' )
		);

		$this->assertFalse(
			$instance->isBrowsableType( '_bar' )
		);
	}

	public function testGetFieldType() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		$instance->registerDataType( '_foo', 'FooValue', DataItem::TYPE_BLOB, false, true );

		$this->assertEquals(
			'_txt',
			$instance->getFieldType( '_foo' )
		);
	}

	protected function assertRegistryFindsIdForLabels( $inputLabel, array $equivalentLabels ) {

		$id = '_wpg';

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [ $inputLabel => $id ] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $instance->findTypeByLabel( $caseVariant ) );
		}
	}

	protected function assertRegistryFindsIdForAliases( $inputLabel, array $equivalentLabels ) {
		$id = '_wpg';

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [ $id => $inputLabel ] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $instance->findTypeByLabel( $caseVariant ) );
		}
	}

	public function testExtensionData() {

		$lang = $this->getMockBuilder( '\SMW\Lang\Lang' )
			->disableOriginalConstructor()
			->getMock();

		$lang->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->will( $this->returnValue( [] ) );

		$lang->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->will( $this->returnValue( [] ) );

		$instance = new DataTypeRegistry(
			$lang
		);

		$instance->registerDataType(
			'__foo', '\SMW\Tests\FooValue', DataItem::TYPE_NOTYPE, 'FooValue'
		);

		$instance->setExtensionData( '__foo', [ 'ext.test' => 'test' ] );

		$this->assertEquals(
			[ 'ext.test' => 'test' ],
			$instance->getExtensionData( '__foo' )
		);
	}

}

class FooValue {
}
