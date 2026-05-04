<?php

namespace SMW\Tests\Unit\SQLStore\QueryDependency;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DependencyLinksTableUpdaterTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $testEnvironment;
	private $spyLogger;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DependencyLinksTableUpdater::class,
			new DependencyLinksTableUpdater( $this->store )
		);
	}

	public function testAddToUpdateList() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 1001 );

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

		$capturedUpdateTables = [];
		$capturedUpdateSets = [];
		$capturedUpdateWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedUpdateTables,
			$capturedUpdateSets,
			$capturedUpdateWheres
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'timestamp' )->willReturn( '20260503000000' );
		$connection->method( 'newDeleteQueryBuilder' )->willReturn( $deleteBuilder );
		$connection->method( 'newInsertQueryBuilder' )->willReturn( $insertBuilder );
		$connection->method( 'newUpdateQueryBuilder' )->willReturn( $updateBuilder );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new DependencyLinksTableUpdater(
			$store
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->clear();

		$instance->addToUpdateList( 42, [ WikiPage::newFromText( 'Bar' ) ] );
		$instance->doUpdate();

		$this->assertSame( [ SQLStore::QUERY_LINKS_TABLE ], $capturedDeleteTables );
		$this->assertSame( [ [ 's_id' => 42 ] ], $capturedDeleteWheres );

		$this->assertSame( [ SQLStore::QUERY_LINKS_TABLE ], $capturedInsertTables );
		$this->assertSame(
			[ [ [ 's_id' => 42, 'o_id' => 1001 ] ] ],
			$capturedInsertRows
		);

		$this->assertSame( [ SQLStore::ID_TABLE ], $capturedUpdateTables );
		$this->assertSame( [ [ 'smw_touched' => '20260503000000' ] ], $capturedUpdateSets );
		$this->assertSame( [ [ 'smw_id' => 42 ] ], $capturedUpdateWheres );
	}

	public function testAddToUpdateListOnNull_List() {
		$instance = new DependencyLinksTableUpdater(
			$this->store
		);

		$this->assertNull(
			$instance->addToUpdateList( 42, null )
		);
	}

	public function testAddToUpdateListOnZero_Id() {
		$instance = new DependencyLinksTableUpdater(
			$this->store
		);

		$this->assertNull(
			$instance->addToUpdateList( 0, [] )
		);
	}

	public function testAddToUpdateListOnEmpty_List() {
		$instance = new DependencyLinksTableUpdater(
			$this->store
		);

		$this->assertNull(
			$instance->addToUpdateList( 42, [] )
		);
	}

	public function testAddDependenciesFromQueryResultWhereObjectIdIsYetUnknownWhichRequiresToCreateTheIdOnTheFly() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getId', 'makeSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturn( 0 );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->willReturn( 1001 );

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

		$capturedUpdateTables = [];
		$capturedUpdateSets = [];
		$capturedUpdateWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedUpdateTables,
			$capturedUpdateSets,
			$capturedUpdateWheres
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'timestamp' )->willReturn( '20260503000000' );
		$connection->method( 'newDeleteQueryBuilder' )->willReturn( $deleteBuilder );
		$connection->method( 'newInsertQueryBuilder' )->willReturn( $insertBuilder );
		$connection->method( 'newUpdateQueryBuilder' )->willReturn( $updateBuilder );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new DependencyLinksTableUpdater(
			$store
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->clear();

		$instance->addToUpdateList( 42, [ WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY ) ] );
		$instance->doUpdate();

		$this->assertSame( [ SQLStore::QUERY_LINKS_TABLE ], $capturedDeleteTables );
		$this->assertSame( [ [ 's_id' => 42 ] ], $capturedDeleteWheres );

		$this->assertSame( [ SQLStore::QUERY_LINKS_TABLE ], $capturedInsertTables );
		$this->assertSame(
			[ [ [ 's_id' => 42, 'o_id' => 1001 ] ] ],
			$capturedInsertRows
		);

		$this->assertSame( [ SQLStore::ID_TABLE ], $capturedUpdateTables );
		$this->assertSame( [ [ 'smw_touched' => '20260503000000' ] ], $capturedUpdateSets );
		$this->assertSame( [ [ 'smw_id' => 42 ] ], $capturedUpdateWheres );
	}

}
