<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcher;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Connection\Database;
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

}
