<?php

namespace SMW\Tests\SQLStore;

use SMW\InMemoryPoolCache;
use SMW\SQLStore\RedirectStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\RedirectStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectStoreTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $conection;
	private $cache;
	private $testEnvironment;
	private $connectionManager;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->store->setConnectionManager( $this->connectionManager );

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );

		InMemoryPoolCache::getInstance()->clear();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		InMemoryPoolCache::getInstance()->clear();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RedirectStore::class,
			new RedirectStore( $this->store )
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
				$this->equalTo( [
					's_title' => 'Foo',
					's_namespace' => 0 ] ) )
			->will( $this->returnValue( $row ) );

		$instance = new RedirectStore(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->findRedirect( 'Foo', 0 )
		);

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertEquals(
			0,
			$stats['sql.store.redirect.infostore']['hits']
		);

		$instance->findRedirect( 'Foo', 0 );

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
				$this->equalTo( [
					's_title' => 'Foo',
					's_namespace' => 0 ] ) )
			->will( $this->returnValue( false ) );

		$instance = new RedirectStore(
			$this->store
		);

		$this->assertEquals(
			0,
			$instance->findRedirect( 'Foo', 0 )
		);
	}

	public function testAddRedirectInfoRecordToFetchFromCache() {

		$this->connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->anything(),
				$this->equalTo( [
					's_title' => 'Foo',
					's_namespace' => 0,
					'o_id' => 42 ] ) );

		$instance = new RedirectStore(
			$this->store
		);

		$instance->addRedirect( 42, 'Foo', 0 );

		$this->assertEquals(
			42,
			$instance->findRedirect( 'Foo', 0 )
		);
	}

	public function testDeleteRedirectInfoRecord() {

		$this->connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->anything(),
				$this->equalTo( [
					's_title' => 'Foo',
					's_namespace' => 9001 ] ) );

		$instance = new RedirectStore(
			$this->store
		);

		$instance->deleteRedirect( 'Foo', 9001 );

		$this->assertEquals(
			0,
			$instance->findRedirect( 'Foo', 9001 )
		);
	}

	public function testUpdateRedirect() {

		$row = new \stdClass;
		$row->ns = NS_MAIN;
		$row->t = 'Bar';

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [ 'Foo' => 42 ] ) )
			->will( $this->returnValue( [ $row ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'getFields' )
			->will( $this->returnValue( [ 'Foo' => \SMW\SQLStore\TableBuilder\FieldType::FIELD_ID ] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->setConnectionManager( $this->connectionManager );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $propertyTable ] ) );

		$store->setOption(
			\SMW\Store::OPT_CREATE_UPDATE_JOB,
			true
		);

		$this->testEnvironment->addConfiguration(
			'smwgEnableUpdateJobs',
			true
		);

		$store->setOption(
			'smwgEnableUpdateJobs',
			true
		);

		$this->jobQueue->expects( $this->once() )
			->method( 'push' );

		$instance = new RedirectStore(
			$store
		);

		$instance->setEqualitySupportFlag( SMW_EQ_FULL );
		$instance->updateRedirect( 42, 'Foo', NS_MAIN );
	}

	public function testUpdateRedirectNotEnabled() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->never() )
			->method( 'getPropertyTables' );

		$store->setOption(
			\SMW\Store::OPT_CREATE_UPDATE_JOB,
			false
		);

		$instance = new RedirectStore(
			$store
		);

		$instance->setEqualitySupportFlag( SMW_EQ_NONE );
		$instance->updateRedirect( 42, 'Foo', NS_MAIN );
	}

}
