<?php

namespace SMW\Tests;

use SMW\PropertySpecificationLookup;
use SMW\DIProperty;
use SMWDIContainer as DIContainer;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIBlob as DIBlob;
use SMWDINumber as DINumber;

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

	private $store;
	private $blobStore;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\PropertySpecificationLookup',
			new PropertySpecificationLookup( $this->store, $this->blobStore )
		);
	}

	public function testGetPropertyDescriptionForPredefinedProperty() {

		$instance = new PropertySpecificationLookup(
			$this->store,
			$this->blobStore
		);

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionFor( new DIProperty( '_PDESC' ) )
		);
	}

	public function testGetPropertyDescriptionForPredefinedPropertyViaCacheForLanguageCode() {

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

		$instance = new PropertySpecificationLookup(
			$this->store,
			$this->blobStore
		);

		$instance->setLanguageCode( 'en' );

		$this->assertEquals(
			1001,
			$instance->getPropertyDescriptionFor( new DIProperty( '_PDESC' ) )
		);
	}

	public function testTryToGetLocalPropertyDescriptionForUserdefinedProperty() {

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$property = new DIProperty( 'SomeProperty' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( new DIProperty( '_PDESC' ) ),
				$this->anything() )
			->will( $this->returnValue( array(
				new DIContainer( ContainerSemanticData::makeAnonymousContainer() ) ) ) );

		$instance = new PropertySpecificationLookup(
			$this->store,
			$this->blobStore
		);

		$this->assertInternalType(
			'string',
			$instance->getPropertyDescriptionFor( $property )
		);
	}

	public function testGetNonCachedDisplayUnit() {

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->blobStore->expects( $this->once() )
			->method( 'save' );

		$property = new DIProperty( 'SomeProperty' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( new DIProperty( '_UNIT' ) ),
				$this->anything() )
			->will( $this->returnValue( array(
				new DIBlob( 'abc,def' ), new DIBlob( '123' ) ) ) );

		$instance = new PropertySpecificationLookup(
			$this->store,
			$this->blobStore
		);

		$this->assertEquals(
			array( 'abc', 'def', '123' ),
			$instance->getDisplayUnitsFor( $property )
		);
	}

	public function testGetNonCachedDisplayPrecision() {

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->blobStore->expects( $this->once() )
			->method( 'save' );

		$property = new DIProperty( 'SomeProperty' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $property->getDiWikiPage() ),
				$this->equalTo( new DIProperty( '_PREC' ) ),
				$this->anything() )
			->will( $this->returnValue( array(
				new DINumber( -2.3 ) ) ) );

		$instance = new PropertySpecificationLookup(
			$this->store,
			$this->blobStore
		);

		$this->assertEquals(
			2,
			$instance->getDisplayPrecisionFor( $property )
		);
	}

}
