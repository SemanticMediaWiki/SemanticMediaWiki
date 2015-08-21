<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\Lookup\CachedValueLookupStore;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;

/**
 * @covers \SMW\SQLStore\Lookup\CachedValueLookupStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class CachedValueLookupStoreTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\CachedValueLookupStore',
			new CachedValueLookupStore( $store, $blobStore )
		);
	}

	public function testGetSemanticDataFromFallbackForDisabledFeature() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getSemanticData' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->setValueLookupFeatures( SMW_VL_PL );

		$instance->getSemanticData( $subject );
	}

	public function testGetSemanticDataFromFallbackForDisabledBlobStore() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getSemanticData' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( false ) );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->getSemanticData( $subject );
	}

	public function testGetSemanticDataFromFallbackForNonAvailableContainer() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'setLastModified' );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getSemanticData' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() )
			->will( $this->returnValue( $semanticData ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( false ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$blobStore->expects( $this->exactly( 2 ) )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$blobStore->expects( $this->exactly( 2 ) )
			->method( 'save' );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->setValueLookupFeatures( SMW_VL_SD );

		$instance->getSemanticData( $subject );
	}

	public function testGetSemanticDataFromCacheForAvailableHash() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$filter  = false;

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$container->expects( $this->once() )
			->method( 'get' )
			->with($this->stringContains( 'sd:' ) )
			->will( $this->returnValue( 'Foo' ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->setValueLookupFeatures( SMW_VL_SD );

		$this->assertEquals(
			'Foo',
			$instance->getSemanticData( $subject, $filter )
		);
	}

	public function testGetPropertiesFromCacheForAvailableHash() {

		$expected = array(
			new DIProperty( 'Bar' )
		);

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard->expects( $this->once() )
			->method( 'isCircularByRecursionFor' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getRedirectTarget' )
			->will( $this->returnValue( new DIProperty( 'Bar' ) ) );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$container->expects( $this->once() )
			->method( 'get' )
			->with($this->stringContains( 'pl:' ) )
			->will( $this->returnValue( $expected ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->setCircularReferenceGuard( $circularReferenceGuard );
		$instance->setValueLookupFeatures( SMW_VL_PL );

		$this->assertEquals(
			$expected,
			$instance->getProperties( $subject )
		);
	}

	public function testGetPropertyValuesFromCacheForAvailableHash() {

		$expected = array(
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard->expects( $this->once() )
			->method( 'isCircularByRecursionFor' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getRedirectTarget' )
			->will( $this->returnValue( new DIWikiPage( 'Bar', NS_MAIN ) ) );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$container->expects( $this->once() )
			->method( 'get' )
			->with($this->stringContains( 'pv:' ) )
			->will( $this->returnValue( $expected ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->setCircularReferenceGuard( $circularReferenceGuard );
		$instance->setValueLookupFeatures( SMW_VL_PV );

		$this->assertEquals(
			$expected,
			$instance->getPropertyValues( $subject, new DIProperty( 'Foobar' ) )
		);
	}

	public function testGetPropertySubjectsFromCacheForAvailableHash() {

		$expected = array(
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$dataItem = new DIWikiPage( 'Foo', NS_MAIN );

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->never() )
			->method( 'getRedirectTarget' );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$container->expects( $this->once() )
			->method( 'get' )
			->with($this->stringContains( 'ps:' ) )
			->will( $this->returnValue( $expected ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->setCircularReferenceGuard( $circularReferenceGuard );
		$instance->setValueLookupFeatures( SMW_VL_PS );

		$this->assertEquals(
			$expected,
			$instance->getPropertySubjects( new DIProperty( 'Foobar' ), $dataItem )
		);
	}

	public function testDeleteFor() {

		$subject = new DIWikiPage( 'Foobar', NS_MAIN, '', 'abc' );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->at( 0 ) )
			->method( 'has' )
			->with( $this->stringContains( 'sd:' ) )
			->will( $this->returnValue( true ) );

		$container->expects( $this->at( 1 ) )
			->method( 'get' )
			->with( $this->stringContains( 'sd:' ) )
			->will( $this->returnValue( $semanticData ) );

		$container->expects( $this->at( 2 ) )
			->method( 'has' )
			->with( $this->stringContains( 'list' ) )
			->will( $this->returnValue( true ) );

		$container->expects( $this->at( 3 ) )
			->method( 'get' )
			->with( $this->stringContains( 'list' ) )
			->will( $this->returnValue( array( 'abc', '123' ) ) );

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->any() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$blobStore->expects( $this->exactly( 4 ) )
			->method( 'delete' );

		$instance = new CachedValueLookupStore(
			$store,
			$blobStore
		);

		$instance->setValueLookupFeatures( SMW_VL_SD );

		$instance->deleteFor( $subject );
	}

}
