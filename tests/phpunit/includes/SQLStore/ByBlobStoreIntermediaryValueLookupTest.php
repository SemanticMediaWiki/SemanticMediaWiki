<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\ByBlobStoreIntermediaryValueLookup;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\SQLStore\ByBlobStoreIntermediaryValueLookup
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ByBlobStoreIntermediaryValueLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ByBlobStoreIntermediaryValueLookup',
			new ByBlobStoreIntermediaryValueLookup( $store, $blobStore )
		);
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

		$instance = new ByBlobStoreIntermediaryValueLookup(
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

		$blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$blobStore->expects( $this->once() )
			->method( 'save' );

		$instance = new ByBlobStoreIntermediaryValueLookup(
			$store,
			$blobStore
		);

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

		$instance = new ByBlobStoreIntermediaryValueLookup(
			$store,
			$blobStore
		);

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

		$instance = new ByBlobStoreIntermediaryValueLookup(
			$store,
			$blobStore
		);

		$instance->setCircularReferenceGuard( $circularReferenceGuard );

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

		$instance = new ByBlobStoreIntermediaryValueLookup(
			$store,
			$blobStore
		);

		$instance->setCircularReferenceGuard( $circularReferenceGuard );

		$this->assertEquals(
			$expected,
			$instance->getPropertyValues( $subject, new DIProperty( 'Foobar' ) )
		);
	}

}
