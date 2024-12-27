<?php

namespace SMW\Tests;

use SMW\DataTypeRegistry;
use SMWDataItem as DataItem;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataTypeRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DataTypeRegistryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $dataTypeRegistry;

	protected function setUp(): void {
		parent::setUp();

		$this->dataTypeRegistry = DataTypeRegistry::getInstance();
	}

	protected function tearDown(): void {
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

	public function testRegisterDatatypeWithCallable() {
		$callback = function () {
			return new FooValue();
		};

		$this->dataTypeRegistry->registerDataType(
			'_foo', $callback, DataItem::TYPE_NOTYPE, 'FooValue'
		);

		$this->assertTrue(
			$this->dataTypeRegistry->hasDataTypeClassById( '_foo' )
		);

		$this->assertInstanceOf(
			'\Closure',
			$this->dataTypeRegistry->getDataTypeClassById( '_foo' )
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
		$this->assertIsString(

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
		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [ '_wpg' => 'Page' ] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [ 'URI'  => '_uri' ] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
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
		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [ 'URI'  => '_uri' ] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		$this->assertEquals(
			[ 'URI'  => '_uri' ],
			$instance->getKnownTypeAliases(),
			'Asserts that getKnownTypeAliases returns an array'
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

		$this->assertSame(
			'_num',
			$this->dataTypeRegistry->findTypeByLabelAndLanguage( 'Number', 'Foo' )
		);
	}

	public function testFindTypeByLabelAndLanguageFromRegisteredTypeWithoutLanguageMatch() {
		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( $localLanguage );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'findDatatypeByLabel' )
			->willReturn( '' );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		$instance->registerDataType( '_foo', 'FooValue', DataItem::TYPE_NOTYPE, 'Foo' );

		$this->assertSame(
			'_foo',
			$instance->findTypeByLabelAndLanguage( 'Foo', 'en' )
		);
	}

	/**
	 * @dataProvider recordTypeProvider
	 */
	public function testIsRecordType( $typeId, $expected ) {
		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		$this->assertEquals(
			$expected,
			$instance->isRecordType( $typeId )
		);
	}

	public function testSubDataType() {
		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		$instance->registerDataType( '_foo', 'FooValue', DataItem::TYPE_NOTYPE, false, true );

		$this->assertTrue(
			$instance->isSubDataType( '_foo' )
		);
	}

	public function testBrowsableType() {
		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
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
		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		$instance->registerDataType( '_foo', 'FooValue', DataItem::TYPE_BLOB, false, true );

		$this->assertEquals(
			'_txt',
			$instance->getFieldType( '_foo' )
		);
	}

	protected function assertRegistryFindsIdForLabels( $inputLabel, array $equivalentLabels ) {
		$id = '_wpg';

		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [ $inputLabel => $id ] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $instance->findTypeByLabel( $caseVariant ) );
		}
	}

	protected function assertRegistryFindsIdForAliases( $inputLabel, array $equivalentLabels ) {
		$id = '_wpg';

		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [ $id => $inputLabel ] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		foreach ( $equivalentLabels as $caseVariant ) {
			$this->assertEquals( $id, $instance->findTypeByLabel( $caseVariant ) );
		}
	}

	public function testRegisterCallableGetCallablesByTypeId() {
		$callback = function () {
			return 'foo';
		};

		$localLanguage = $this->getMockBuilder( '\SMW\Localizer\LocalLanguage\LocalLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeLabels' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getDatatypeAliases' )
			->willReturn( [] );

		$localLanguage->expects( $this->once() )
			->method( 'getCanonicalDatatypeLabels' )
			->willReturn( [] );

		$instance = new DataTypeRegistry(
			$localLanguage
		);

		$instance->registerDataType(
			'__foo', '\SMW\Tests\FooValue', DataItem::TYPE_NOTYPE, 'FooValue'
		);

		$instance->registerCallable(
			'__foo', 'ext.test', $callback
		);

		$this->assertEquals(
			[ 'ext.test' => $callback ],
			$instance->getCallablesByTypeId( '__foo' )
		);
	}

	public function recordTypeProvider() {
		yield [
			'_rec',
			true
		];

		yield [
			'_ref_rec',
			true
		];

		yield [
			'_mlt_rec',
			true
		];

		yield [
			'_foo',
			false
		];
	}

}

class FooValue {
}
