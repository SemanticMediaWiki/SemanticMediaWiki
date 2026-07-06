<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\EntityStore\PrefetchCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchCacheTest extends TestCase {

	private $store;
	private $prefetchItemLookup;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->prefetchItemLookup = $this->getMockBuilder( PrefetchItemLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( RequestOptions::class )
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
		$property = new Property( 'Foo' );
		$subject = WikiPage::newFromText( __METHOD__ );

		$expected = [
			WikiPage::newFromText( 'Bar' )
		];

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getSMWPageID' )
			->with( __METHOD__ )
			->willReturn( 42 );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( [ 42 => [ WikiPage::newFromText( 'Bar' ) ] ] );

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
		$property = new Property( 'Pm' );
		$subject1 = WikiPage::newFromText( 'Subject1' );
		$subject2 = WikiPage::newFromText( 'Subject2' );

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->method( 'getSMWPageID' )
			->willReturnOnConsecutiveCalls( 101, 102 );

		$this->store->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->prefetchItemLookup->method( 'getPropertyValues' )
			->willReturnOnConsecutiveCalls(
				[ 101 => [ WikiPage::newFromText( 'Result1' ) ] ],
				[ 102 => [ WikiPage::newFromText( 'Result2' ) ] ]
			);

		$instance = new PrefetchCache(
			$this->store,
			$this->prefetchItemLookup
		);

		$instance->prefetch( [ $subject1 ], $property, $this->requestOptions );
		$instance->prefetch( [ $subject2 ], $property, $this->requestOptions );

		$this->assertEquals(
			[ WikiPage::newFromText( 'Result1' ) ],
			$instance->getPropertyValues( $subject1, $property, $this->requestOptions )
		);

		$this->assertEquals(
			[ WikiPage::newFromText( 'Result2' ) ],
			$instance->getPropertyValues( $subject2, $property, $this->requestOptions )
		);
	}

}
