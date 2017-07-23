<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\IdMatchFinder;
use SMW\IteratorFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\EntityStore\IdMatchFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class IdMatchFinderTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $cache;
	private $iteratorFactory;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			IdMatchFinder::class,
			new IdMatchFinder( $connection, $this->iteratorFactory, $this->cache )
		);
	}

	public function testGetDataItemForNonCachedId() {

		$row = new \stdClass;
		$row->smw_title = 'Foo';
		$row->smw_namespace = 0;
		$row->smw_iw = '';
		$row->smw_subobject ='';

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( 42 ),
				$this->anything() );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( 'Foo#14##' ) );

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

		$instance = new IdMatchFinder(
			$connection,
			$this->iteratorFactory,
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemById( 42 )
		);
	}

	public function testGetDataItemForCachedId() {

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'contains' )
			->with(	$this->equalTo( 42 ) )
			->will( $this->returnValue( true ) );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( 'Foo#14##' ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'selectRow' );

		$instance = new IdMatchFinder(
			$connection,
			$this->iteratorFactory,
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemById( 42 )
		);
	}

	public function testPredefinedPropertyItem() {

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'contains' )
			->with(	$this->equalTo( 42 ) )
			->will( $this->returnValue( true ) );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( '_MDAT#102##' ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->never() )
			->method( 'selectRow' );

		$instance = new IdMatchFinder(
			$connection,
			$this->iteratorFactory,
			$this->cache
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemById( 42 )
		);
	}

	public function testNullForUnknownId() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$instance = new IdMatchFinder(
			$connection,
			$this->iteratorFactory,
			$this->cache
		);

		$this->assertNull(
			$instance->getDataItemById( 42 )
		);
	}

	public function testGetDataItemsFromList() {

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

		$instance = new IdMatchFinder(
			$connection,
			new IteratorFactory(),
			$this->cache
		);

		foreach ( $instance->getDataItemsFromList( array( 42 ) ) as $value ) {
			$this->assertEquals(
				'Foo#0##',
				$value
			);
		}
	}

}
