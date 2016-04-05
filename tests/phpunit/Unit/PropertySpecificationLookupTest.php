<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\PropertySpecificationLookup;
use SMWContainerSemanticData as ContainerSemanticData;

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

	protected function setUp() {
		parent::setUp();

		$this->cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataItemFactory = new DataItemFactory();
		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->resetPoolCacheFor( PropertySpecificationLookup::POOLCACHE_ID );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PropertySpecificationLookup',
			new PropertySpecificationLookup( $this->cachedPropertyValuesPrefetcher )
		);
	}

	public function testGetPropertyFromDisplayTitle() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'queryPropertyValuesFor' )
			->will( $this->returnValue( array( $this->dataItemFactory->newDIWikiPage( 'Foo' ) ) ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher
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

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher
		);

		$this->assertTrue(
			$instance->hasUniquenessConstraintFor( $property )
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

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher
		);

		$this->assertEquals(
			'IPv4',
			$instance->getAllowedPatternFor( $property )
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

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher
		);

		$this->assertEquals(
			$expected,
			$instance->getAllowedValuesFor( $property )
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

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher
		);

		$this->assertEquals(
			2,
			$instance->getDisplayPrecisionFor( $property )
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
			$this->cachedPropertyValuesPrefetcher
		);

		$this->assertEquals(
			array( 'abc', 'def', '123' ),
			$instance->getDisplayUnitsFor( $property )
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
			$this->cachedPropertyValuesPrefetcher
		);

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionFor( $property )
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
			$this->cachedPropertyValuesPrefetcher
		);

		$instance->setLanguageCode( 'en' );

		$this->assertEquals(
			1001,
			$instance->getPropertyDescriptionFor( $property )
		);
	}

	public function testTryToGetLocalPropertyDescriptionForUserdefinedProperty() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

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
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_PDESC' ) ),
				$this->anything() )
			->will( $this->returnValue( array(
				 $this->dataItemFactory->newDIContainer( ContainerSemanticData::makeAnonymousContainer() ) ) ) );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $this->blobStore ) );

		$instance = new PropertySpecificationLookup(
			$this->cachedPropertyValuesPrefetcher
		);

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionFor( $property )
		);
	}

}
