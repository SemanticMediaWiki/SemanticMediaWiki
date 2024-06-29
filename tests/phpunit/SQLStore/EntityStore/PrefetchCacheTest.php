<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\PrefetchCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchCacheTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $prefetchItemLookup;
	private $requestOptions;

	protected function setUp() : void {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->prefetchItemLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\PrefetchItemLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PrefetchCache::class,
			new PrefetchCache( $this->store, $this->prefetchItemLookup )
		);
	}

	public function testCacheAndFetch() {

		$property = new DIProperty( 'Foo' );
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$expected = [
			DIWikiPage::newFromText( 'Bar' )
		];

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getSMWPageID' )
			->with( $this->equalTo( __METHOD__ ) )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->prefetchItemLookup->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ 42 => [ DIWikiPage::newFromText( 'Bar' ) ] ] ) );

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject ], $property, $this->requestOptions );

		$this->assertEquals(
			$expected,
			$instance->getPropertyValues( $subject, $property, $this->requestOptions )
		);
	}

	public function testCacheMergeWithDifferentFingerprints() {
		$property = new DIProperty('Pm');
		$subject1 = DIWikiPage::newFromText("Subject1");
		$subject2 = DIWikiPage::newFromText("Subject2");
	
		$idTable = $this->getMockBuilder('\SMW\SQLStore\EntityStore\EntityIdManager')
			->disableOriginalConstructor()
			->getMock();
	
		$idTable->method('getSMWPageID')
			->willReturnOnConsecutiveCalls(101, 102);
	
		$this->store->method('getObjectIds')
			->willReturn($idTable);
	
		$this->prefetchItemLookup->method('getPropertyValues')
			->willReturnOnConsecutiveCalls(
				[101 => [DIWikiPage::newFromText('Result1')]],
				[102 => [DIWikiPage::newFromText('Result2')]]
			);
	
		$instance = new PrefetchCache($this->store, $this->prefetchItemLookup);
		
		// First prefetch
		$instance->prefetch([$subject1], $property, $this->requestOptions);
	
		// Second prefetch with a different fingerprint, that is generated in PrefetchCache
		$instance->prefetch([$subject2], $property, $this->requestOptions);
	
		// Verify that both results are present in the cache
		$this->assertEquals(
			[DIWikiPage::newFromText('Result1')],
			$instance->getPropertyValues($subject1, $property, $this->requestOptions)
		);
		$this->assertEquals(
			[DIWikiPage::newFromText('Result2')],
			$instance->getPropertyValues($subject2, $property, $this->requestOptions)
		);
	}
}
