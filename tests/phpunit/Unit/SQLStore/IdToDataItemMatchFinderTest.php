<?php

namespace SMW\Tests\SQLStore;

use SMW\InMemoryPoolCache;
use SMW\SQLStore\IdToDataItemMatchFinder;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\IdToDataItemMatchFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class IdToDataItemMatchFinderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() {
		$this->testEnvironment->resetPoolCacheFor( IdToDataItemMatchFinder::POOLCACHE_ID );
	}

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\IdToDataItemMatchFinder',
			new IdToDataItemMatchFinder( $connection )
		);
	}

	public function testGetDataItemForNonCachedId() {

		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject ='';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( array( 'smw_id' => 42 ) ) )
			->will( $this->returnValue( $row ) );

		$instance = new IdToDataItemMatchFinder(
			$connection
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemForId( 42 )
		);

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertEquals(
			1,
			$stats[IdToDataItemMatchFinder::POOLCACHE_ID]['count']
		);
	}

	public function testGetDataItemForCachedId() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'selectRow' );

		InMemoryPoolCache::getInstance()->getPoolCacheFor( IdToDataItemMatchFinder::POOLCACHE_ID )->save( 42, 'Foo#0##' );

		$instance = new IdToDataItemMatchFinder(
			$connection
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemForId( 42 )
		);

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertEquals(
			0,
			$stats[IdToDataItemMatchFinder::POOLCACHE_ID]['misses']
		);

		$this->assertEquals(
			1,
			$stats[IdToDataItemMatchFinder::POOLCACHE_ID]['hits']
		);
	}

	public function testPredefinedPropertyItem() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'selectRow' );

		$instance = new IdToDataItemMatchFinder(
			$connection
		);

		$instance->saveToCache( 42, '_MDAT#102##' );

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemForId( 42 )
		);
	}

	public function testSaveDeleteFromCaceh() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$instance = new IdToDataItemMatchFinder(
			$connection
		);

		$instance->saveToCache( 42, 'Foo#14##' );
		$instance->getDataItemForId( 42 );

		$instance->deleteFromCache( 42 );
		$instance->getDataItemForId( 42 );
	}

	public function testClearCache() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$instance = new IdToDataItemMatchFinder(
			$connection
		);

		$instance->saveToCache( 42, 'Foo#0##' );
		$instance->getDataItemForId( 42 );

		$instance->clear();
		$instance->getDataItemForId( 42 );
	}

	public function testNullForUnknownId() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$instance = new IdToDataItemMatchFinder( $connection );

		$this->assertNull(
			$instance->getDataItemForId( 42 )
		);
	}

	public function testGetDataItemPoolHashListFor() {

		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject ='';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( array( 'smw_id' => array( 42 ) ) ) )
			->will( $this->returnValue( array( $row ) ) );

		$instance = new IdToDataItemMatchFinder(
			$connection
		);

		$this->assertEquals(
			array( 'Foo#0##' ),
			$instance->getDataItemPoolHashListFor( array( 42 ) )
		);
	}

}
