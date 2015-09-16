<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\ByIdDataItemFinder;
use SMW\InMemoryPoolCache;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\ByIdDataItemFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class ByIdDataItemFinderTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		InMemoryPoolCache::getInstance()->clear();
	}

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ByIdDataItemFinder',
			new ByIdDataItemFinder( $connection )
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

		$instance = new ByIdDataItemFinder(
			$connection
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemForId( 42 )
		);

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertEquals(
			1,
			$stats['sql.store.dataitem.finder']['count']
		);
	}

	public function testGetDataItemForCachedId() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'selectRow' );

		InMemoryPoolCache::getInstance()->getPoolCacheFor( 'sql.store.dataitem.finder' )->save( 42, 'Foo#0##' );

		$instance = new ByIdDataItemFinder(
			$connection
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemForId( 42 )
		);

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertEquals(
			0,
			$stats['sql.store.dataitem.finder']['misses']
		);

		$this->assertEquals(
			1,
			$stats['sql.store.dataitem.finder']['hits']
		);
	}

	public function testPredefinedPropertyItem() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'selectRow' );

		$instance = new ByIdDataItemFinder(
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

		$instance = new ByIdDataItemFinder(
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

		$instance = new ByIdDataItemFinder(
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

		$instance = new ByIdDataItemFinder( $connection );

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

		$instance = new ByIdDataItemFinder(
			$connection
		);

		$this->assertEquals(
			array( 'Foo#0##' ),
			$instance->getDataItemPoolHashListFor( array( 42 ) )
		);
	}

}
