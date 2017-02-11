<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\EntityStore\CachedEntityLookup;

/**
 * @covers \SMW\SQLStore\EntityStore\CachedEntityLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class CachedEntityLookupTest extends \PHPUnit_Framework_TestCase {

	private $entityLookup;
	private $redirectTargetLookup;
	private $blobStore;

	protected function setUp() {
		parent::setUp();

		$this->entityLookup = $this->getMockBuilder( '\SMW\EntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->redirectTargetLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\RedirectTargetLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\CachedEntityLookup',
			new CachedEntityLookup( $this->entityLookup, $this->redirectTargetLookup, $this->blobStore )
		);
	}

	public function testGetSemanticDataFromFallbackForDisabledFeature() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$this->entityLookup->expects( $this->once() )
			->method( 'getSemanticData' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$this->blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->setCachedLookupFeatures( SMW_VL_PL );
		$instance->getSemanticData( $subject );
	}

	public function testGetSemanticDataFromFallbackForDisabledBlobStore() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$this->entityLookup->expects( $this->once() )
			->method( 'getSemanticData' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() );

		$this->blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( false ) );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->getSemanticData( $subject );
	}

	public function testGetSemanticDataFromFallbackForNonAvailableContainer() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'setOption' )
			->with(
				$this->equalTo( $semanticData::OPT_LAST_MODIFIED ),
				$this->anything() );

		$this->entityLookup->expects( $this->once() )
			->method( 'getSemanticData' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() )
			->will( $this->returnValue( $semanticData ) );

		$container = $this->getMockBuilder( '\Onoi\BlobStore\Container' )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->once() )
			->method( 'has' )
			->will( $this->returnValue( false ) );

		$this->blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->exactly( 2 ) )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->blobStore->expects( $this->exactly( 2 ) )
			->method( 'save' );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->setCachedLookupFeatures( SMW_VL_SD );
		$instance->getSemanticData( $subject );
	}

	public function testGetSemanticDataFromCacheForAvailableHash() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$filter  = false;

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

		$this->blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->setCachedLookupFeatures( SMW_VL_SD );

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

		$this->redirectTargetLookup->expects( $this->once() )
			->method( 'findRedirectTarget' )
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

		$this->blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->setCachedLookupFeatures( SMW_VL_PL );

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

		$this->redirectTargetLookup->expects( $this->once() )
			->method( 'findRedirectTarget' )
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

		$this->blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->setCachedLookupFeatures( SMW_VL_PV );

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

		$this->redirectTargetLookup->expects( $this->never() )
			->method( 'findRedirectTarget' );

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

		$this->blobStore->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->setCachedLookupFeatures( SMW_VL_PS );

		$this->assertEquals(
			$expected,
			$instance->getPropertySubjects( new DIProperty( 'Foobar' ), $dataItem )
		);
	}

	public function testResetCacheBy() {

		$subject = new DIWikiPage( 'Foobar', NS_MAIN, '', 'abc' );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			new DIWikiPage( 'Bar', NS_MAIN )
		);

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

		$this->blobStore->expects( $this->any() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$this->blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$this->blobStore->expects( $this->exactly( 4 ) )
			->method( 'delete' );

		$instance = new CachedEntityLookup(
			$this->entityLookup,
			$this->redirectTargetLookup,
			$this->blobStore
		);

		$instance->setCachedLookupFeatures( SMW_VL_SD );
		$instance->resetCacheBy( $subject );
	}

}
