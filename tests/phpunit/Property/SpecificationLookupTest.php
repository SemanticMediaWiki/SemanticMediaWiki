<?php

namespace SMW\Tests\Property;

use SMW\DataItemFactory;
use SMW\Property\SpecificationLookup;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDataItem as DataItem;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Property\SpecificationLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SpecificationLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $monolingualTextLookup;
	private $dataItemFactory;
	private $testEnvironment;
	private $store;
	private $entityCache;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'save', 'fetch', 'associate', 'fetchSub', 'saveSub', 'delete', 'invalidate' ] )
			->getMock();

		$this->monolingualTextLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\MonolingualTextLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataItemFactory = new DataItemFactory();
		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SpecificationLookup::class,
			new SpecificationLookup( $this->store, $this->entityCache )
		);
	}

	public function testGetSpecification() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( 'Bar' ),
				$this->anything() );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->getSpecification(
			$property,
			$this->dataItemFactory->newDIProperty( 'Bar' )
		);
	}

	public function testGetSpecification_SkipCache() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( 'Bar' ),
				$this->anything() );

		$this->entityCache->expects( $this->never() )
			->method( 'fetchSub' );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->skipCache();

		$instance->getSpecification(
			$property,
			$this->dataItemFactory->newDIProperty( 'Bar' )
		);
	}

	public function testGetFieldList() {
		$property = $this->dataItemFactory->newDIProperty( 'RecordProperty' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_LIST' ),
				$this->anything() )
			->willReturn( [
					$this->dataItemFactory->newDIBlob( 'Foo' ),
					$this->dataItemFactory->newDIBlob( 'abc;123' ) ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			'abc;123',
			$instance->getFieldListBy( $property )
		);
	}

	public function testGetPreferredPropertyLabel() {
		$property = $this->dataItemFactory->newDIProperty( 'SomeProperty' );
		$property->setPropertyTypeId( '_mlt_rec' );

		$this->store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );

		$this->monolingualTextLookup->expects( $this->once() )
			->method( 'newDataValue' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PPLB' ),
				$this->anything() );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertSame(
			'',
			$instance->getPreferredPropertyLabelByLanguageCode( $property )
		);
	}

	public function testHasUniquenessConstraint() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVUC' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertTrue(
			$instance->hasUniquenessConstraint( $property )
		);
	}

	public function testGetExternalFormatterUri() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PEFU' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIUri( 'http', 'example.org/$1' ) ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertInstanceOf(
			DataItem::class,
			$instance->getExternalFormatterUri( $property )
		);
	}

	public function testGetAllowedPattern() {
		$property = $this->dataItemFactory->newDIProperty( 'Has allowed pattern' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVAP' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'IPv4' ) ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			'IPv4',
			$instance->getAllowedPatternBy( $property )
		);
	}

	public function testGetAllowedListValueBy() {
		$property = $this->dataItemFactory->newDIProperty( 'Has list' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVALI' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'Foo' ) ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			[ 'Foo' ],
			$instance->getAllowedListValues( $property )
		);
	}

	public function testGetAllowedValues() {
		$expected = [
			$this->dataItemFactory->newDIBlob( 'A' ),
			$this->dataItemFactory->newDIBlob( 'B' )
		];

		$property = $this->dataItemFactory->newDIProperty( 'Has allowed values' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PVAL' ),
				$this->anything() )
			->willReturn( $expected );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			$expected,
			$instance->getAllowedValues( $property )
		);
	}

	public function testGetDisplayPrecision() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PREC' ),
				$this->anything() )
			->willReturn( [ $this->dataItemFactory->newDINumber( -2.3 ) ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			2,
			$instance->getDisplayPrecision( $property )
		);
	}

	public function testgetDisplayUnits() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_UNIT' ),
				$this->anything() )
			->willReturn( [
				$this->dataItemFactory->newDIBlob( 'abc,def' ),
				$this->dataItemFactory->newDIBlob( '123' ) ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			[ 'abc', 'def', '123' ],
			$instance->getDisplayUnits( $property )
		);
	}

	public function testGetPropertyDescriptionForPredefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );

		$this->monolingualTextLookup->expects( $this->once() )
			->method( 'newDataValue' )
			->with(
				$property->getDiWikiPage(),
				$this->dataItemFactory->newDIProperty( '_PDESC' ),
				$this->anything() );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getPropertyDescriptionByLanguageCode( $property )
		);
	}

	public function testGetPropertyDescriptionForPredefinedPropertyViaCacheForLanguageCode() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->with(
				$this->stringContains( 'smw:entity:propertyspecificationlookup:description:1313b81bb6a61a4661f7b91408659f86' ),
				'en:0' )
			->willReturn( 1001 );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			1001,
			$instance->getPropertyDescriptionByLanguageCode( $property, 'en' )
		);
	}

	public function testTryToGetLocalPropertyDescriptionForUserdefinedProperty() {
		$stringValue = $this->getMockBuilder( '\SMW\DataValues\StringValue' )
			->disableOriginalConstructor()
			->getMock();

		$monolingualTextValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMock();

		$monolingualTextValue->expects( $this->once() )
			->method( 'getTextValueByLanguageCode' )
			->with(	'foo' )
			->willReturn( $stringValue );

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'service' )
			->willReturn( $this->monolingualTextLookup );

		$this->monolingualTextLookup->expects( $this->once() )
			->method( 'newDataValue' )
			->with(
				$property->getDiWikiPage(),
				$this->anything(),
				$this->anything() )
			->willReturn( $monolingualTextValue );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertIsString(

			$instance->getPropertyDescriptionByLanguageCode( $property, 'foo' )
		);
	}

	public function testGetPropertyGroup() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$ppgr = $this->dataItemFactory->newDIProperty( '_PPGR' );

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_CATEGORY );
		$bool = $this->dataItemFactory->newDIBoolean( true );

		$this->store->expects( $this->at( 0 ) )
			->method( 'getPropertyValues' )
			->with(
				$property->getDiWikiPage(),
				$this->anything(),
				$this->anything() )
			->willReturn( [ $dataItem ] );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getPropertyValues' )
			->with(
				$dataItem,
				$ppgr,
				$this->anything() )
			->willReturn( [ $bool ] );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$this->assertEquals(
			$dataItem,
			$instance->getPropertyGroup( $property )
		);
	}

	public function testInvalidateCache() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$this->entityCache->expects( $this->at( 0 ) )
			->method( 'invalidate' )
			->with( $subject );

		$this->entityCache->expects( $this->at( 1 ) )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:entity:propertyspecificationlookup:44ab375ee7ebac04b8e4471a70180dc5' ) );

		$this->entityCache->expects( $this->at( 2 ) )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:entity:propertyspecificationlookup:preferredlabel:44ab375ee7ebac04b8e4471a70180dc5' ) );

		$this->entityCache->expects( $this->at( 3 ) )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:entity:propertyspecificationlookup:description:44ab375ee7ebac04b8e4471a70180dc5' ) );

		$instance = new SpecificationLookup(
			$this->store,
			$this->entityCache
		);

		$instance->invalidateCache( $subject );
	}

}
