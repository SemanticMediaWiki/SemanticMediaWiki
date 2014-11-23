<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\RedirectInfoStore;
use SMW\Cache\FixedInMemoryCache;

/**
 * @covers \SMW\SQLStore\RedirectInfoStore
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-sqlstore
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectInfoStoreTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\RedirectInfoStore',
			new RedirectInfoStore( $connection )
		);
	}

	public function testFindRedirectIdForNonCachedRedirect() {

		$row = new \stdClass;
		$row->o_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 0 ) ) )
			->will( $this->returnValue( $row ) );

		$cache = new FixedInMemoryCache();
		$instance = new RedirectInfoStore( $connection, $cache );

		$this->assertEquals(
			42,
			$instance->findRedirectIdFor( 'Foo', 0 )
		);

		$stats = $cache->getStats();

		$this->assertEquals(
			0,
			$stats['hits']
		);

		$instance->findRedirectIdFor( 'Foo', 0 );

		$stats = $cache->getStats();

		$this->assertEquals(
			1,
			$stats['hits']
		);
	}

	public function testFindRedirectIdForNonCachedNonRedirect() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 0 ) ) )
			->will( $this->returnValue( false ) );

		$instance = new RedirectInfoStore( $connection );

		$this->assertEquals(
			0,
			$instance->findRedirectIdFor( 'Foo', 0 )
		);
	}

	public function testAddRedirectInfoRecord() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 0,
					'o_id' => 42 ) ) );

		$instance = new RedirectInfoStore( $connection );
		$instance->addRedirectForId( 42, 'Foo', 0 );

		$this->assertEquals(
			42,
			$instance->findRedirectIdFor( 'Foo', 0 )
		);
	}

	public function testDeleteRedirectInfoRecord() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 9001 ) ) );

		$instance = new RedirectInfoStore( $connection );
		$instance->deleteRedirectEntry( 'Foo', 9001 );

		$this->assertEquals(
			0,
			$instance->findRedirectIdFor( 'Foo', 9001 )
		);
	}

}
