<?php

namespace SMW\Tests\Unit\SQLStore;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\InMemoryPoolCache;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobQueue;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\RedirectStore
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectStoreTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $store;
	private Database $connection;
	private $cache;
	private $testEnvironment;
	private $connectionManager;
	private JobQueue $jobQueue;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->connectionManager = $this->getMockBuilder( ConnectionManager::class )
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
		$row = new stdClass;
		$row->o_id = 42;

		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new RedirectStore(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->findRedirect( 'Foo', 0 )
		);

		$this->assertSame(
			[ [ 's_title' => 'Foo', 's_namespace' => 0 ] ],
			$capturedWheres
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
		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [], $capturedWheres );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new RedirectStore(
			$this->store
		);

		$this->assertSame(
			0,
			$instance->findRedirect( 'Foo', 0 )
		);

		$this->assertSame(
			[ [ 's_title' => 'Foo', 's_namespace' => 0 ] ],
			$capturedWheres
		);
	}

	public function testAddRedirectInfoRecordToFetchFromCache() {
		// insert() does a selectRow duplicate-check (returns false)
		$capturedSelectWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [], $capturedSelectWheres );

		$capturedDeleteTables = [];
		$capturedDeleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedDeleteTables,
			$capturedDeleteWheres
		);

		$capturedInsertTables = [];
		$capturedInsertRows = [];
		$insertBuilder = $this->createMockInsertQueryBuilder(
			$capturedInsertTables,
			$capturedInsertRows
		);

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$instance = new RedirectStore(
			$this->store
		);

		$instance->addRedirect( 42, 'Foo', 0 );

		$this->assertSame(
			[ [ 's_title' => 'Foo', 's_namespace' => 0, 'o_id' => 42 ] ],
			$capturedSelectWheres
		);

		$this->assertSame(
			[ [ 's_title' => 'Foo', 's_namespace' => 0 ] ],
			$capturedDeleteWheres
		);

		$this->assertSame(
			[ [
				's_title' => 'Foo',
				's_namespace' => 0,
				'o_id' => 42,
			] ],
			$capturedInsertRows
		);

		$this->assertEquals(
			42,
			$instance->findRedirect( 'Foo', 0 )
		);
	}

	public function testDeleteRedirectInfoRecord() {
		$capturedDeleteTables = [];
		$capturedDeleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedDeleteTables,
			$capturedDeleteWheres
		);

		// findRedirect() after delete() will go to select() (cache was cleared)
		$selectBuilder = $this->createMockSelectQueryBuilder( [] );

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new RedirectStore(
			$this->store
		);

		$instance->deleteRedirect( 'Foo', 9001 );

		$this->assertSame(
			[ [
				's_title' => 'Foo',
				's_namespace' => 9001,
			] ],
			$capturedDeleteWheres
		);

		$this->assertSame(
			0,
			$instance->findRedirect( 'Foo', 9001 )
		);
	}

	public function testUpdateRedirect() {
		$row = new stdClass;
		$row->ns = NS_MAIN;
		$row->t = 'Bar';

		// delete() in deleteRedirect() uses newDeleteQueryBuilder()
		$deleteBuilder = $this->createMockDeleteQueryBuilder();

		// findUpdateJobs() uses newSelectQueryBuilder()->fetchResultSet()
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ] );

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'getFields' )
			->willReturn( [ 'Foo' => FieldType::FIELD_ID ] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->setConnectionManager( $this->connectionManager );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTable ] );

		$store->setOption(
			Store::OPT_CREATE_UPDATE_JOB,
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
			->with( SQLStore::UPDATE_TRANSACTION )
			->willReturn( true );

		$this->jobQueue->expects( $this->once() )
			->method( 'lazyPush' );

		$row = new stdClass;
		$row->ns = NS_MAIN;
		$row->t = 'Bar';

		$deleteBuilder = $this->createMockDeleteQueryBuilder();
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ] );

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'getFields' )
			->willReturn( [ 'Foo' => FieldType::FIELD_ID ] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->setConnectionManager( $this->connectionManager );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTable ] );

		$store->setOption(
			Store::OPT_CREATE_UPDATE_JOB,
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
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->never() )
			->method( 'getPropertyTables' );

		$store->setOption(
			Store::OPT_CREATE_UPDATE_JOB,
			false
		);

		$instance = new RedirectStore(
			$store
		);

		$instance->setEqualitySupport( SMW_EQ_NONE );
		$instance->updateRedirect( 42, 'Foo', NS_MAIN );
	}

}
