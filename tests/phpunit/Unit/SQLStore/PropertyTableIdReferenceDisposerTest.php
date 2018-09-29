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
class PropertyTableIdReferenceDisposerTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->will( $this->returnValue( DIWikiPage::newFromText( 'Foo' ) ) );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$jobQueueGroup = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->any() )
			->method( 'lazyPush' );

		$this->testEnvironment->registerObject( 'JobQueueGroup', $jobQueueGroup );
	}

	protected function tearDown() {
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
			->with( $this->equalTo( 42 ) )
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableIdReferenceFinder' )
			->will( $this->returnValue( $propertyTableIdReferenceFinder ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
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
			->will( $this->returnValue( false ) );

		$connection->expects( $this->at( 0 ) )
			->method( 'delete' )
			->with( $this->equalTo( \SMW\SQLStore\SQLStore::ID_TABLE ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $tableDefinition ] ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableIdReferenceFinder' )
			->will( $this->returnValue( $propertyTableIdReferenceFinder ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->removeOutdatedEntityReferencesById( 42 );
	}

	public function testCleanUpTableEntriesFor() {

		$tableDefinition = $connection = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'delete' )
			->with( $this->equalTo( \SMW\SQLStore\SQLStore::ID_TABLE ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $tableDefinition ] ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCanConstructOutdatedEntitiesResultIterator() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newOutdatedEntitiesResultIterator()
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
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->cleanUpTableEntriesByRow( $row );
	}

	public function testCleanUpOnTransactionIdle() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return call_user_func( $callback );
			}
			) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$instance->waitOnTransactionIdle();
		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCleanUpOnTransactionIdleAvoidOnSubobject() {

		if ( !method_exists( '\PHPUnit_Framework_MockObject_Builder_InvocationMocker', 'withConsecutive' ) ) {
			$this->markTestSkipped( 'PHPUnit_Framework_MockObject_Builder_InvocationMocker::withConsecutive requires PHPUnit 5.7+.' );
		}

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->will( $this->returnValue( new DIWikiPage( 'Foo', NS_MAIN, '', 'Bar' ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'onTransactionIdle' )
			->will( $this->returnCallback( function( $callback ) {
				return $callback();
			} ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->withConsecutive(
				[ $this->equalTo( SQLStore::ID_TABLE ) ],
				[ $this->equalTo( SQLStore::PROPERTY_STATISTICS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::FT_SEARCH_TABLE ) ]
			);

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$store
		);

		$instance->waitOnTransactionIdle();
		$instance->cleanUpTableEntriesById( 42 );
	}

	public function testCleanUp_Redirect() {

		if ( !method_exists( '\PHPUnit_Framework_MockObject_Builder_InvocationMocker', 'withConsecutive' ) ) {
			$this->markTestSkipped( 'PHPUnit_Framework_MockObject_Builder_InvocationMocker::withConsecutive requires PHPUnit 5.7+.' );
		}

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemById' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getDataItemById' )
			->will( $this->returnValue( new DIWikiPage( 'Foo', NS_MAIN, SMW_SQL3_SMWREDIIW ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		// No SQLStore::ID_TABLE
		$connection->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->withConsecutive(
				[ $this->equalTo( SQLStore::PROPERTY_STATISTICS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::QUERY_LINKS_TABLE ) ],
				[ $this->equalTo( SQLStore::FT_SEARCH_TABLE ) ]
			);

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [] ) );

		$instance = new PropertyTableIdReferenceDisposer(
			$store
		);

		$instance->cleanUpTableEntriesById( 42 );
	}

}
