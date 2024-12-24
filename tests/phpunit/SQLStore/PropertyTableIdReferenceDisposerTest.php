<?php

namespace SMW\Tests\SQLStore;

use SMW\DIWikiPage;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\PropertyTableIdReferenceDisposer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceDisposerTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $testEnvironment;
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( DIWikiPage::newFromText( 'Foo' ) );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->any() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableIdReferenceDisposer::class,
			new PropertyTableIdReferenceDisposer( $this->store )
		);
	}

	public function testIsDisposable() {
		$propertyTableIdReferenceFinder = $connection = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableIdReferenceFinder' )
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
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->isDisposable( 42 )
		);
	}

	public function testTryToRemoveOutdatedEntryFromIDTable() {
		$tableDefinition = $connection = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableIdReferenceFinder = $connection = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableIdReferenceFinder' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$connection->expects( $this->at( 0 ) )
			->method( 'delete' )
			->with( \SMW\SQLStore\SQLStore::ID_TABLE );

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
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->removeOutdatedEntityReferencesById( 42 );
	}

	public function testCleanUpTableEntriesFor() {
		$tableDefinition = $connection = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$connection->expects( $this->at( 3 ) )
			->method( 'delete' )
			->with( \SMW\SQLStore\SQLStore::ID_TABLE );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ $tableDefinition ] );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCanConstructOutdatedEntitiesResultIterator() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newOutdatedEntitiesResultIterator()
		);
	}

	public function testCanConstructByNamespaceInvalidEntitiesResultIterator() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( [] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newByNamespaceInvalidEntitiesResultIterator()
		);
	}

	public function testCleanUpTableEntriesByRow() {
		$row = new \stdClass;
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesByRow( $row );
	}

	public function testCleanUpOnTransactionIdle() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) {
				return call_user_func( $callback );
			}
			);

		$connection->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->waitOnTransactionIdle();
		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCleanUpOnTransactionIdleAvoidOnSubobject() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar' ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( $callback ) {
				return $callback();
			} );

		$connection->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->withConsecutive(
				[ $this->equalTo( SQLStore::ID_TABLE ) ],
				[ $this->equalTo( SQLStore::ID_AUXILIARY_TABLE ) ],
				[ $this->equalTo( SQLStore::PROPERTY_STATISTICS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::FT_SEARCH_TABLE ) ]
			);

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$store
		);

		$instance->waitOnTransactionIdle();
		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCleanUp_Redirect() {
		if ( !method_exists( '\PHPUnit\Framework\MockObject\Builder\InvocationMocker', 'withConsecutive' ) ) {
			$this->markTestSkipped( 'PHPUnit\Framework\MockObject\Builder\InvocationMocker::withConsecutive requires PHPUnit 5.7+.' );
		}

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( new DIWikiPage( 'Foo', NS_MAIN, SMW_SQL3_SMWREDIIW ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		// No SQLStore::ID_TABLE
		$connection->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->withConsecutive(
				[ $this->equalTo( SQLStore::ID_AUXILIARY_TABLE ) ],
				[ $this->equalTo( SQLStore::PROPERTY_STATISTICS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::FT_SEARCH_TABLE ) ]
			);

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceDisposer(
			$store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->cleanUpTableEntriesById( 42 );
	}

}
