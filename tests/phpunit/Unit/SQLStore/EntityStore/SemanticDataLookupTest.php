<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDIBlob as DIBlob;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\SemanticDataLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SemanticDataLookupTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $connection;
	private $dataItemHandler;
	private $query;

	public function setUp() {
		parent::setUp();

		$this->dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'findPropertyTableID', 'getDataItemHandlerForDIType', 'getObjectIds' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $this->dataItemHandler ) );

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'tableName' )
			->will( $this->returnArgument(0) );

		$this->query = new \SMW\MediaWiki\Connection\Query( $this->connection );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->store->setConnectionManager( $connectionManager );
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SemanticDataLookup::class,
			new SemanticDataLookup( $this->store )
		);
	}

	public function testNewStubSemanticData_FromDIWikiPage() {

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\StubSemanticData',
			$instance->newStubSemanticData( DIWikiPage::newFromText( __METHOD__ ) )
		);
	}

	public function testNewStubSemanticData_FromSemanticData() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\StubSemanticData',
			$instance->newStubSemanticData( $semanticData )
		);
	}

	public function testNewStubSemanticDataThrowsException() {

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->newStubSemanticData( 'Foo' );
	}

	public function testGetTableUsageInfo() {

		$property =  new DIProperty( 'Foo' );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->with(	$this->equalTo( $property ) )
			->will( $this->returnValue( '__bar__' ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ $property ] ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertEquals(
			[ '__bar__' => true ],
			$instance->getTableUsageInfo( $semanticData )
		);
	}

	public function testNewRequestOptions_NULL() {

		$property =  new DIProperty( 'Foo' );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertEquals(
			null,
			$instance->newRequestOptions( $propertyTable, $property )
		);
	}

	public function testNewRequestOptions_AsConditionConstraint_IsFixedPropertyTable() {

		$property =  new DIProperty( 'Foo' );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$requestOptions = new RequestOptions();
		$requestOptions->conditionConstraint = true;

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\RequestOptions',
			$instance->newRequestOptions( $propertyTable, $property, $requestOptions )
		);
	}

	public function testSemanticDataFromTable() {

		$row = new \stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->will( $this->returnValue( [ 'fooField' => 'fieldType' ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->will( $this->returnValue( 'Foo' ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $this->query ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$semanticData = $instance->fetchSemanticDataFromTable(
			42,
			$subject,
			$propertyTable
		);
	}

	public function testSemanticDataFromTable_WithConstraint() {

		$row = new \stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 9999 ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->will( $this->returnValue( [ 'fooField' => 'fieldType' ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->will( $this->returnValue( 'Foo' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnCallback( function( $value ) { return "'$value'"; } ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $this->query ) );

		$subject = DIWikiPage::newFromText( __METHOD__ );

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

		$row = new \stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->will( $this->returnValue( [ 'fooField' => 'fieldType' ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->will( $this->returnValue( 'Foo' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnCallback( function( $value ) { return "'$value'"; } ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $this->query ) );

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

		$row = new \stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->will( $this->returnValue( [ 'fooField' => 'fieldType' ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( false ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->will( $this->returnValue( 'Foo' ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'bar_table' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnCallback( function( $value ) { return "'$value'"; } ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $this->query ) );

		$dataItem = DIWikiPage::newFromText( 'no_id_subject' );

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

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( false ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertEquals(
			[],
			$instance->fetchSemanticDataFromTable( 42, null, $propertyTable )
		);
	}

	public function testGetSemanticData() {

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = DIWikiPage::newFromText( 'Foo' );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\StubSemanticData',
			$instance->getSemanticData( 42, $dataItem, $propertyTable )
		);
	}

	public function testGetSemanticData_NonWikiPage_ThrowsException() {

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getSemanticData( 42, null, $propertyTable );
	}

	public function testFetchSemanticDataFromTable_NonWikiPageTable_DISTINCT_SELECT() {

		$row = new \stdClass;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->will( $this->returnValue( [ 'fooField' => 'fieldType' ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->will( $this->returnValue( 'Foo' ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'bar_table' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnCallback( function( $value ) { return "'$value'"; } ) );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->returnValue( $this->query ) );

		$dataItem = new DIBlob( __METHOD__ );

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

		$query_1 = new \SMW\MediaWiki\Connection\Query( $this->connection );
		$query_2 = new \SMW\MediaWiki\Connection\Query( $this->connection );

		$row = new \stdClass;
		$row->p_id = 9000;
		$row->prop = 'FOO';
		$row->v0 = '1001';

		$this->dataItemHandler->expects( $this->any() )
			->method( 'getFetchFields' )
			->will( $this->returnValue( [ 'fooField' => 'fieldType' ] ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$propertyTable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getDIType' )
			->will( $this->returnValue( 'Foo' ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'bar_table' ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnCallback( function( $value ) { return "'$value'"; } ) );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( [ $row ] ) );

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->will( $this->onConsecutiveCalls( $query_1, $query_2 ) );

		$dataItem = DIWikiPage::newFromText( 'Bar' );

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
