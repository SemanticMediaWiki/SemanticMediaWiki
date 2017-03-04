<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\PropertySpecificationLookup;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\PropertySpecificationLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertySpecificationLookupTest extends \PHPUnit_Framework_TestCase {

	private $blobStore;
	private $dataItemFactory;
	private $testEnvironment;
	private $cachedPropertyValuesPrefetcher;
	private $intermediaryMemoryCache;

	protected function setUp() {
		parent::setUp();

		$this->cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->intermediaryMemoryCache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataItemFactory = new DataItemFactory();
		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PropertySpecificationLookup',
			new PropertySpecificationLookup( $this->cachedPropertyValuesPrefetcher, $this->intermediaryMemoryCache )
		);
	}

	public function testGetSpecification() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( 'Bar' ) ),
				$this->anything() );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$instance->getSpecification(
			$property,
			$this->dataItemFactory->newDIProperty( 'Bar' )
		);
	}

	public function testGetFieldList() {

		$property = $this->dataItemFactory->newDIProperty( 'RecordProperty' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_LIST' ) ),
				$this->anything() )
			->will(
				$this->returnValue( array(
					$this->dataItemFactory->newDIBlob( 'Foo' ),
					$this->dataItemFactory->newDIBlob( 'abc;123' ) ) ) );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			'abc;123',
			$instance->getFieldListBy( $property )
		);
	}

	public function testGetPreferredPropertyLabel() {

		$property = $this->dataItemFactory->newDIProperty( 'SomeProperty' );
		$property->setPropertyTypeId( '_mlt_rec' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PPLB' ) ),
				$this->anything() );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			'',
			$instance->getPreferredPropertyLabelBy( $property )
		);
	}

	public function testGetPropertyFromDisplayTitle() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'queryPropertyValuesFor' )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIWikiPage( 'Foo' ) ) ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			$property,
			$instance->getPropertyFromDisplayTitle( 'abc' )
		);
	}

	public function testHasUniquenessConstraint() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PVUC' ) ),
				$this->anything() )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIBoolean( true ) ) ) );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertTrue(
			$instance->hasUniquenessConstraintBy( $property )
		);
	}

	public function testGetExternalFormatterUri() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PEFU' ) ),
				$this->anything() )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIUri( 'http', 'example.org/$1' ) ) ) );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertInstanceOf(
			DataItem::class,
			$instance->getExternalFormatterUriBy( $property )
		);
	}

	public function testGetAllowedPattern() {

		$property = $this->dataItemFactory->newDIProperty( 'Has allowed pattern' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PVAP' ) ),
				$this->anything() )
			->will(
				$this->returnValue( array( $this->dataItemFactory->newDIBlob( 'IPv4' ) ) ) );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			'IPv4',
			$instance->getAllowedPatternBy( $property )
		);
	}

	public function testGetAllowedListValueBy() {

		$property = $this->dataItemFactory->newDIProperty( 'Has list' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PVALI' ) ),
				$this->anything() )
			->will(
				$this->returnValue( array( $this->dataItemFactory->newDIBlob( 'Foo' ) ) ) );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			array( 'Foo' ),
			$instance->getAllowedListValueBy( $property )
		);
	}

	public function testGetAllowedValues() {

		$expected =  array(
			$this->dataItemFactory->newDIBlob( 'A' ),
			$this->dataItemFactory->newDIBlob( 'B' )
		);

		$property = $this->dataItemFactory->newDIProperty( 'Has allowed values' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PVAL' ) ),
				$this->anything() )
			->will( $this->returnValue( $expected ) );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			$expected,
			$instance->getAllowedValuesBy( $property )
		);
	}

	public function testGetDisplayPrecision() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PREC' ) ),
				$this->anything() )
			->will( $this->returnValue( array( $this->dataItemFactory->newDINumber( -2.3 ) ) ) );

		$this->intermediaryMemoryCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			2,
			$instance->getDisplayPrecisionBy( $property )
		);
	}

	public function testgetDisplayUnits() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_UNIT' ) ),
				$this->anything() )
			->will( $this->returnValue( array(
				$this->dataItemFactory->newDIBlob( 'abc,def' ),
				$this->dataItemFactory->newDIBlob( '123' ) ) ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			array( 'abc', 'def', '123' ),
			$instance->getDisplayUnitsBy( $property )
		);
	}

	public function testGetPropertyDescriptionForPredefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $this->blobStore ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionBy( $property )
		);
	}

	public function testGetPropertyDescriptionForPredefinedPropertyViaCacheForLanguageCode() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$container->expects( $this->once() )
			->method( 'get' )
			->with( $this->stringContains( 'pdesc:en:0' ) )
			->will( $this->returnValue( 1001 ) );

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $this->blobStore ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertEquals(
			1001,
			$instance->getPropertyDescriptionBy( $property, 'en' )
		);
	}

	public function testTryToGetLocalPropertyDescriptionForUserdefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$pdesc = $this->dataItemFactory->newDIProperty( '_PDESC' );
		$pdesc->setPropertyTypeId( '_mlt_rec' );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->anything(),
				$this->anything() )
			->will( $this->returnValue( array(
				$this->dataItemFactory->newDIContainer( ContainerSemanticData::makeAnonymousContainer() ) ) ) );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $this->blobStore ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher,
			$this->intermediaryMemoryCache
		);

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionBy( $property )
		);
	}

}
