<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcher;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\PropertyTableIdReferenceFinder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\PropertyTableIdReferenceDisposer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceDisposerTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $store;
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( WikiPage::newFromText( 'Foo' ) );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableIdReferenceDisposer::class,
			new PropertyTableIdReferenceDisposer( $this->store, $this->eventDispatcher )
		);
	}

	public function testIsDisposable() {
		$propertyTableIdReferenceFinder = $connection = $this->getMockBuilder( PropertyTableIdReferenceFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableIdReferenceFinder->expects( $this->any() )
			->method( 'hasResidualReferenceForId' )
			->with( 42 )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableIdReferenceFinder' )
			->willReturn( $propertyTableIdReferenceFinder );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->isDisposable( 42 )
		);
	}

	public function testTryToRemoveOutdatedEntryFromIDTable() {
		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableIdReferenceFinder = $this->getMockBuilder( PropertyTableIdReferenceFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$deleteBuilder = $this->createMockDeleteQueryBuilder();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ $tableDefinition ] );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableIdReferenceFinder' )
			->willReturn( $propertyTableIdReferenceFinder );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$instance->removeOutdatedEntityReferencesById( 42 );
	}

	public function testCleanUpTableEntriesFor() {
		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$tableDefinition->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'smw_test_table' );

		$deleteBuilder = $this->createMockDeleteQueryBuilder();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ $tableDefinition ] );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCanConstructOutdatedEntitiesResultIterator() {
		$queryBuilder = $this->createMockSelectQueryBuilder( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newOutdatedEntitiesResultIterator()
		);
	}

	public function testCanConstructByNamespaceInvalidEntitiesResultIterator() {
		$queryBuilder = $this->createMockSelectQueryBuilder( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newByNamespaceInvalidEntitiesResultIterator()
		);
	}

	public function testOutdatedEntitiesResultIterator_AppliesShardModulo() {
		$whereConditions = [];
		$queryBuilder = $this->createMockSelectQueryBuilder( [], $whereConditions );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_OF, 4 );
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_INDEX, 1 );

		$instance->newOutdatedEntitiesResultIterator( $requestOptions );

		$this->assertContains( 'smw_id % 4 = 1', $whereConditions );
	}

	public function testByNamespaceInvalidEntitiesResultIterator_AppliesShardModulo() {
		$whereConditions = [];
		$queryBuilder = $this->createMockSelectQueryBuilder( [], $whereConditions );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_OF, 3 );
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_INDEX, 2 );

		$instance->newByNamespaceInvalidEntitiesResultIterator( $requestOptions );

		$this->assertContains( 'smw_id % 3 = 2', $whereConditions );
	}

	public function testOutdatedEntitiesResultIterator_NoShardLeavesWhereUnchanged() {
		$whereConditions = [];
		$queryBuilder = $this->createMockSelectQueryBuilder( [], $whereConditions );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		// Without shard options (OPT_SHARD_OF defaults to 1), no modulo
		// condition is added; only the base where() remains.
		$instance->newOutdatedEntitiesResultIterator( new RequestOptions() );

		$this->assertSame(
			[ [ 'smw_iw' => SMW_SQL3_SMWDELETEIW ] ],
			$whereConditions
		);
	}

	public function testOutdatedEntitiesResultIterator_ShardedRequestAppliesNoNegativeLimit() {
		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$capturedUseIndex = [];
		$capturedLimits = [];
		$queryBuilder = $this->createMockSelectQueryBuilder( [], $whereConditions, $capturedSelects, $capturedTables, $capturedUseIndex, $capturedLimits );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		// Sharded selection without an explicit limit: RequestOptions::$limit
		// defaults to -1 ("no limit"). The iterator must NOT translate that
		// into `LIMIT -1`, which MariaDB rejects (error 1064).
		$requestOptions = new RequestOptions();
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_OF, 2 );
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_INDEX, 0 );

		$instance->newOutdatedEntitiesResultIterator( $requestOptions );

		$this->assertSame(
			[],
			$capturedLimits,
			'No LIMIT clause should be applied for a sharded/no-limit request.'
		);
	}

	public function testByNamespaceInvalidEntitiesResultIterator_ShardedRequestAppliesNoNegativeLimit() {
		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$capturedUseIndex = [];
		$capturedLimits = [];
		$queryBuilder = $this->createMockSelectQueryBuilder( [], $whereConditions, $capturedSelects, $capturedTables, $capturedUseIndex, $capturedLimits );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_OF, 2 );
		$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_INDEX, 0 );

		$instance->newByNamespaceInvalidEntitiesResultIterator( $requestOptions );

		$this->assertSame(
			[],
			$capturedLimits,
			'No LIMIT clause should be applied for a sharded/no-limit request.'
		);
	}

	public function testOutdatedEntitiesResultIterator_PositiveLimitIsApplied() {
		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$capturedUseIndex = [];
		$capturedLimits = [];
		$queryBuilder = $this->createMockSelectQueryBuilder( [], $whereConditions, $capturedSelects, $capturedTables, $capturedUseIndex, $capturedLimits );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $queryBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		// The EntityIdDisposerJob path sets an explicit positive limit which
		// must still be applied verbatim.
		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 5000 );

		$instance->newOutdatedEntitiesResultIterator( $requestOptions );

		$this->assertSame(
			[ 5000 ],
			$capturedLimits
		);
	}

	public function testCleanUpTableEntriesByRow() {
		$row = new stdClass;
		$row->smw_id = 42;

		$deleteBuilder = $this->createMockDeleteQueryBuilder();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesByRow( $row );
	}

	public function testCleanUpOnTransactionIdle() {
		$deleteBuilder = $this->createMockDeleteQueryBuilder();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( static function ( $callback ) {
				return call_user_func( $callback );
			}
			);

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->eventDispatcher
		);

		$instance->waitOnTransactionIdle();
		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCleanUpOnTransactionIdleAvoidOnSubobject() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( new WikiPage( 'Foo', NS_MAIN, '', 'Bar' ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$capturedTables = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $capturedTables );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( static function ( $callback ) {
				return $callback();
			} );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$store,
			$this->eventDispatcher
		);

		$instance->waitOnTransactionIdle();
		$instance->cleanUpTableEntriesById( 42 );

		$this->assertSame(
			[
				SQLStore::ID_TABLE,
				SQLStore::ID_AUXILIARY_TABLE,
				SQLStore::PROPERTY_STATISTICS_TABLE,
				SQLStore::QUERY_LINKS_TABLE,
				SQLStore::QUERY_LINKS_TABLE,
			],
			$capturedTables
		);
	}

	public function testCleanUp_Redirect() {
		if ( !method_exists( InvocationMocker::class, 'withConsecutive' ) ) {
			$this->markTestSkipped( 'PHPUnit\Framework\MockObject\Builder\InvocationMocker::withConsecutive requires PHPUnit 5.7+.' );
		}

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( new WikiPage( 'Foo', NS_MAIN, SMW_SQL3_SMWREDIIW ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$capturedTables = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $capturedTables );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$store,
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesById( 42 );

		// No SQLStore::ID_TABLE for redirects (without redirectRemoval)
		$this->assertSame(
			[
				SQLStore::ID_AUXILIARY_TABLE,
				SQLStore::PROPERTY_STATISTICS_TABLE,
				SQLStore::QUERY_LINKS_TABLE,
				SQLStore::QUERY_LINKS_TABLE,
			],
			$capturedTables
		);
	}

	public function testCleanUpTableEntriesByIdList_NonRedirect() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( new WikiPage( 'Foo', NS_MAIN, '' ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $capturedTables, $capturedWheres );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$store,
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesByIdList( [ 42, 43 ] );

		$this->assertSame(
			[
				SQLStore::ID_TABLE,
				SQLStore::ID_AUXILIARY_TABLE,
				SQLStore::PROPERTY_STATISTICS_TABLE,
				SQLStore::QUERY_LINKS_TABLE,
				SQLStore::QUERY_LINKS_TABLE,
			],
			$capturedTables
		);

		$this->assertSame( [ 'smw_id' => [ 42, 43 ] ], $capturedWheres[0] );
	}

	public function testCleanUpTableEntriesByIdList_ExcludesRedirectFromIdTable() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturnCallback( static function ( $id ) {
				return $id === 42
					? new WikiPage( 'Foo', NS_MAIN, SMW_SQL3_SMWREDIIW )
					: new WikiPage( 'Bar', NS_MAIN, '' );
			} );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$capturedTables = [];
		$capturedWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $capturedTables, $capturedWheres );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$store,
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesByIdList( [ 42, 43 ] );

		// ID_TABLE delete only for the non-redirect id (43).
		$this->assertSame( [ 'smw_id' => [ 43 ] ], $capturedWheres[0] );
	}

}
