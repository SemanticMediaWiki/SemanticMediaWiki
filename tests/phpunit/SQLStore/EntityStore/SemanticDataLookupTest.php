<?php

namespace SMW\Tests\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\Query;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\SQLStore\EntityStore\StubSemanticData;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use stdClass;
use Wikimedia\Rdbms\FakeResultWrapper;

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

	private $store;
	private $connection;
	private $dataItemHandler;
	private $query;

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

		$this->query = new Query( $this->connection );

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

		$this->connection->expects( $this->once() )
			->method( 'readQuery' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $this->query );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$subject = WikiPage::newFromText( __METHOD__ );

		$semanticData = $instance->fetchSemanticDataFromTable(
			42,
			$subject,
			$propertyTable
		);
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

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static function ( $value ) { return "'$value'";
			} );

		$this->connection->expects( $this->once() )
			->method( 'readQuery' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $this->query );

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

		$this->assertEquals(
			"SELECT p.smw_title AS prop, fooField AS v0 FROM  " .
			"INNER JOIN smw_object_ids AS p ON p_id=p.smw_id " .
			"WHERE (s_id='42') AND (p.smw_iw!=':smw') AND (p.smw_iw!=':smw-delete') AND (p_id='9999') " .
			"LIMIT 4",
			$this->query->build()
		);
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

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static function ( $value ) { return "'$value'";
			} );

		$this->connection->expects( $this->once() )
			->method( 'readQuery' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $this->query );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->fetchSemanticDataFromTable( 42, null, $propertyTable );

		$this->assertEquals(
			"SELECT p.smw_title AS prop, fooField AS v0 FROM  " .
			"INNER JOIN smw_object_ids AS p ON p_id=p.smw_id " .
			"WHERE (s_id='42') AND (p.smw_iw!=':smw') AND (p.smw_iw!=':smw-delete')",
			$this->query->build()
		);
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

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static function ( $value ) { return "'$value'";
			} );

		$this->connection->expects( $this->once() )
			->method( 'readQuery' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $this->query );

		$dataItem = WikiPage::newFromText( 'no_id_subject' );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->fetchSemanticDataFromTable( 42, $dataItem, $propertyTable );

		$this->assertEquals(
			"SELECT p.smw_title AS prop, fooField AS v0 FROM bar_table " .
			"INNER JOIN smw_object_ids AS p ON p_id=p.smw_id " .
			"WHERE (s_title='no_id_subject') AND (s_namespace='0') AND (p.smw_iw!=':smw') AND (p.smw_iw!=':smw-delete')",
			$this->query->build()
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

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static function ( $value ) { return "'$value'";
			} );

		$this->connection->expects( $this->once() )
			->method( 'readQuery' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $this->query );

		$dataItem = new Blob( __METHOD__ );

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 4 );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->fetchSemanticDataFromTable( 42, $dataItem, $propertyTable, $requestOptions );

		$this->assertEquals(
			"SELECT DISTINCT fooField AS v0 FROM bar_table WHERE (p_id='42') LIMIT 4",
			$this->query->build()
		);
	}

	public function testGetSemanticData_OnLimit() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$query_1 = new Query( $this->connection );
		$query_2 = new Query( $this->connection );

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

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnCallback( static function ( $value ) { return "'$value'";
			} );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'readQuery' )
			->willReturnOnConsecutiveCalls( new FakeResultWrapper( [ $row ] ), new FakeResultWrapper( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturnOnConsecutiveCalls( $query_1, $query_2 );

		$dataItem = WikiPage::newFromText( 'Bar' );

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 4 );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->getSemanticData( 42, $dataItem, $propertyTable, $requestOptions );

		$this->assertEquals(
			"SELECT DISTINCT p_id FROM bar_table INNER JOIN smw_object_ids " .
			"AS p ON p_id=p.smw_id WHERE (s_id='42') AND (p.smw_iw!=':smw') AND (p.smw_iw!=':smw-delete')",
			$query_1->build()
		);

		$this->assertEquals(
			"SELECT p.smw_title AS prop, fooField AS v0 FROM bar_table INNER JOIN smw_object_ids " .
			"AS p ON p_id=p.smw_id WHERE (s_id='42') AND (p.smw_iw!=':smw') AND (p.smw_iw!=':smw-delete') AND (p_id='9000') LIMIT 4",
			$query_2->build()
		);
	}

}
