<?php

namespace SMW\Tests\SQLStore;

use SMW\InMemoryPoolCache;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobQueue;
use SMW\SQLStore\RedirectStore;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\RedirectStore
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectStoreTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private Database $connection;
	private $cache;
	private $testEnvironment;
	private $connectionManager;
	private JobQueue $jobQueue;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [] )
			->getMock();

		$this->connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->setConnectionManager( $this->connectionManager );

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );

		InMemoryPoolCache::getInstance()->clear();
	}

	protected function tearDown(): void {
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
			->willReturn( $row );

		$instance = new RedirectStore(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->findRedirect( 'Foo', 0 )
		);

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertSame(
			0,
			$stats['sql.store.redirect.infostore']['hits']
		);

		$instance->findRedirect( 'Foo', 0 );

		$stats = InMemoryPoolCache::getInstance()->getStats();

		$this->assertSame(
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
			->willReturn( false );

		$instance = new RedirectStore(
			$this->store
		);

		$this->assertSame(
			0,
			$instance->findRedirect( 'Foo', 0 )
		);
	}

	public function testAddRedirectInfoRecordToFetchFromCache() {
		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( false );

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

		$this->assertSame(
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
				[ 'Foo' => 42 ] )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'getFields' )
			->willReturn( [ 'Foo' => \SMW\SQLStore\TableBuilder\FieldType::FIELD_ID ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->setConnectionManager( $this->connectionManager );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTable ] );

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
			->method( 'lazyPush' );

		$instance = new RedirectStore(
			$store
		);

		$instance->setCommandLineMode( false );
		$instance->setEqualitySupport( SMW_EQ_FULL );

		$instance->updateRedirect( 42, 'Foo', NS_MAIN );
	}

	public function testUpdateRedirect_OnCommandLine_ActiveSectionTransaction() {
		$this->connection->expects( $this->once() )
			->method( 'inSectionTransaction' )
			->with( \SMW\SQLStore\SQLStore::UPDATE_TRANSACTION )
			->willReturn( true );

		$this->jobQueue->expects( $this->once() )
			->method( 'lazyPush' );

		$row = new \stdClass;
		$row->ns = NS_MAIN;
		$row->t = 'Bar';

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'getFields' )
			->willReturn( [ 'Foo' => \SMW\SQLStore\TableBuilder\FieldType::FIELD_ID ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->setConnectionManager( $this->connectionManager );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTable ] );

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

		$instance = new RedirectStore(
			$store
		);

		$instance->setCommandLineMode( true );
		$instance->setEqualitySupport( SMW_EQ_FULL );

		$instance->updateRedirect( 42, 'Foo', NS_MAIN );
	}

	public function testUpdateRedirectNotEnabled() {
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyTables' ] )
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

		$instance->setEqualitySupport( SMW_EQ_NONE );
		$instance->updateRedirect( 42, 'Foo', NS_MAIN );
	}

}
