<?php

namespace SMW\Tests\SQLStore;

use SMW\InMemoryPoolCache;
use SMW\SQLStore\RedirectInfoStore;

/**
 * @covers \SMW\SQLStore\RedirectInfoStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectInfoStoreTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $conection;
	private $cache;

	protected function setUp() {

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		InMemoryPoolCache::getInstance()->clear();
	}

	protected function tearDown() {
		InMemoryPoolCache::getInstance()->clear();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\RedirectInfoStore',
			new RedirectInfoStore( $this->store )
		);
	}

	public function testFindRedirectIdForNonCachedRedirect() {

		$row = new \stdClass;
		$row->o_id = 42;

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 0 ) ) )
			->will( $this->returnValue( $row ) );

		$instance = new RedirectInfoStore(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->findRedirectIdFor( 'Foo', 0 )
		);

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertEquals(
			0,
			$stats['sql.store.redirect.infostore']['hits']
		);

		$instance->findRedirectIdFor( 'Foo', 0 );

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertEquals(
			1,
			$stats['sql.store.redirect.infostore']['hits']
		);
	}

	public function testFindRedirectIdForNonCachedNonRedirect() {

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 0 ) ) )
			->will( $this->returnValue( false ) );

		$instance = new RedirectInfoStore(
			$this->store
		);

		$this->assertEquals(
			0,
			$instance->findRedirectIdFor( 'Foo', 0 )
		);
	}

	public function testAddRedirectInfoRecordToFetchFromCache() {

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 0,
					'o_id' => 42 ) ) );

		$instance = new RedirectInfoStore(
			$this->store
		);

		$instance->addRedirectForId( 42, 'Foo', 0 );

		$this->assertEquals(
			42,
			$instance->findRedirectIdFor( 'Foo', 0 )
		);
	}

	public function testDeleteRedirectInfoRecord() {

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( array(
					's_title' => 'Foo',
					's_namespace' => 9001 ) ) );

		$instance = new RedirectInfoStore(
			$this->store
		);

		$instance->deleteRedirectEntry( 'Foo', 9001 );

		$this->assertEquals(
			0,
			$instance->findRedirectIdFor( 'Foo', 9001 )
		);
	}

}
