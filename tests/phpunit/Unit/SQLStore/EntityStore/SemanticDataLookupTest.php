<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\SQLStore\EntityStore\StubSemanticData;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\EntityStore\SemanticDataLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SemanticDataLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $store;
	private $connection;
	private $dataItemHandler;

	public function setUp(): void {
		parent::setUp();

		$this->dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'findPropertyTableID', 'getDataItemHandlerForDIType', 'getObjectIds' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $this->dataItemHandler );

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->willReturnArgument( 0 );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->setConnectionManager( $connectionManager );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SemanticDataLookup::class,
			new SemanticDataLookup( $this->store )
		);
	}

	public function testNewStubSemanticData_FromWikiPage() {
		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			StubSemanticData::class,
			$instance->newStubSemanticData( WikiPage::newFromText( __METHOD__ ) )
		);
	}

	public function testNewStubSemanticData_FromSemanticData() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			StubSemanticData::class,
			$instance->newStubSemanticData( $semanticData )
		);
	}

	public function testNewStubSemanticDataThrowsException() {
		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->expectException( 'RuntimeException' );
		$instance->newStubSemanticData( 'Foo' );
	}

	public function testGetTableUsageInfo() {
		$property = new Property( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->with(	$property )
			->willReturn( '__bar__' );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertEquals(
			[ '__bar__' => true ],
			$instance->getTableUsageInfo( $semanticData )
		);
	}

	public function testGetTableUsageInfoSkipsSortkeyProperty() {
		$property = new Property( '_SKEY' );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->with( $property )
			->willReturn( null );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$instance = new SemanticDataLookup(
			$this->store
		);

		// _SKEY is never stored in a property table; findPropertyTableID()
		// returns null for it, so it must not appear in the usage set.
		$this->assertSame(
			[],
			$instance->getTableUsageInfo( $semanticData )
		);
	}

	public function testGetTableUsageInfoSkipsPropertyWithoutDedicatedTable() {
		$property = new Property( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->with( $property )
			->willReturn( '' );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $property ] );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertSame(
			[],
			$instance->getTableUsageInfo( $semanticData )
		);
	}

	public function testGetTableUsageInfoKeepsRealTableWhenSentinelPropertyPresent() {
		$tabled = new Property( 'Foo' );
		$sortkey = new Property( '_SKEY' );

		$this->store->method( 'findPropertyTableID' )
			->willReturnCallback( static function ( Property $property ) {
				return $property->getKey() === 'Foo' ? '__bar__' : null;
			} );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ $tabled, $sortkey ] );

		$instance = new SemanticDataLookup(
			$this->store
		);

		// A real table id is retained even when a sentinel property is skipped.
		$this->assertSame(
			[ '__bar__' => true ],
			$instance->getTableUsageInfo( $semanticData )
		);
	}

	public function testNewRequestOptions_NULL() {
		$property = new Property( 'Foo' );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertNull(
						$instance->newRequestOptions( $propertyTable, $property )
		);
	}

	public function testNewRequestOptions_AsConditionConstraint_IsFixedPropertyTable() {
		$property = new Property( 'Foo' );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$requestOptions = new RequestOptions();
		$requestOptions->conditionConstraint = true;

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			RequestOptions::class,
			$instance->newRequestOptions( $propertyTable, $property, $requestOptions )
		);
	}

	public function testSemanticDataFromTable() {
		$row = new stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'fooField' => 'fieldType' ] );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->willReturn( 'Foo' );

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$subject = WikiPage::newFromText( __METHOD__ );

		$instance->fetchSemanticDataFromTable(
			42,
			$subject,
			$propertyTable
		);

		$this->assertContainsEquals( [ 's_id' => 42 ], $whereConditions );
	}

	public function testSemanticDataFromTable_WithConstraint() {
		$row = new stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 9999 );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'fooField' => 'fieldType' ] );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->willReturn( 'Foo' );

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$requestOptions = new RequestOptions();
		$requestOptions->conditionConstraint = true;
		$requestOptions->setLimit( 4 );

		$requestOptions = $instance->newRequestOptions(
			$propertyTable,
			$property,
			$requestOptions
		);

		$instance->fetchSemanticDataFromTable( 42, $subject, $propertyTable, $requestOptions );

		// `s_id` from the subject restriction and `p_id` from the extra-condition
		// constraint must both be present in the captured where conditions.
		$this->assertContainsEquals( [ 's_id' => 42 ], $whereConditions );
		$this->assertContainsEquals( [ 'p_id' => 9999 ], $whereConditions );
	}

	public function testFetchSemanticDataFromTable_NoDataItem() {
		$row = new stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'fooField' => 'fieldType' ] );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->willReturn( 'Foo' );

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->fetchSemanticDataFromTable( 42, null, $propertyTable );

		$this->assertContainsEquals( [ 's_id' => 42 ], $whereConditions );
	}

	public function testFetchSemanticDataFromTable_NoIdSubject() {
		$row = new stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'fooField' => 'fieldType' ] );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->willReturn( false );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->willReturn( 'Foo' );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'bar_table' );

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$dataItem = WikiPage::newFromText( 'no_id_subject' );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->fetchSemanticDataFromTable( 42, $dataItem, $propertyTable );

		// Subject without idSubject column → restriction by title+namespace.
		$this->assertContainsEquals(
			[
				's_title' => 'no_id_subject',
				's_namespace' => 0,
			],
			$whereConditions
		);
	}

	public function testFetchSemanticDataFromTable_Empty() {
		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->willReturn( false );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertEquals(
			[],
			$instance->fetchSemanticDataFromTable( 42, null, $propertyTable )
		);
	}

	public function testGetSemanticData() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = WikiPage::newFromText( 'Foo' );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			StubSemanticData::class,
			$instance->getSemanticData( 42, $dataItem, $propertyTable )
		);
	}

	public function testGetSemanticData_NonWikiPage_ThrowsException() {
		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->expectException( 'RuntimeException' );
		$instance->getSemanticData( 42, null, $propertyTable );
	}

	public function testFetchSemanticDataFromTable_NonWikiPageTable_DISTINCT_SELECT() {
		$row = new stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'fooField' => 'fieldType' ] );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->willReturn( 'Foo' );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'bar_table' );

		$whereConditions = [];
		$qb = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$dataItem = new Blob( __METHOD__ );

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 4 );

		$instance = new SemanticDataLookup(
			$this->store
		);

		// Non-WikiPage `Blob` data item → property branch (isSubject=false), which
		// applies DISTINCT and a `p_id` restriction. `distinct()` on the trait mock
		// is a `willReturnSelf()` no-op; assert the restriction made it through.
		$instance->fetchSemanticDataFromTable( 42, $dataItem, $propertyTable, $requestOptions );

		$this->assertContainsEquals( [ 'p_id' => 42 ], $whereConditions );
	}

	public function testGetSemanticData_OnLimit() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$row = new stdClass;
		$row->p_id = 9000;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->willReturn( [ 'fooField' => 'fieldType' ] );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$propertyTable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->willReturn( 'Foo' );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'bar_table' );

		$whereConditions = [];
		$qb1 = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );
		$qb2 = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnOnConsecutiveCalls( $qb1, $qb2 );

		$dataItem = WikiPage::newFromText( 'Bar' );

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 4 );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->getSemanticData( 42, $dataItem, $propertyTable, $requestOptions );

		// First query: `fetchPropertiesFromTable` restricts on `s_id`.
		// Second query: `fetchSemanticDataFromTable` re-applies `s_id` and adds the
		// extra-condition `p_id` discovered from the first query's row.
		$this->assertContainsEquals( [ 's_id' => 42 ], $whereConditions );
		$this->assertContainsEquals( [ 'p_id' => 9000 ], $whereConditions );
	}

}
