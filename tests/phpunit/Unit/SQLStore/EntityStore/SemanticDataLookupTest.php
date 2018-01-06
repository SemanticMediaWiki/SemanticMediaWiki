<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\DIWikiPage;
use SMW\RequestOptions;

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

	private $store;
	private $connection;
	private $dataItemHandler;

	public function setUp() {
		parent::setUp();

		$this->dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables', 'getDataItemHandlerForDIType', 'getObjectIds' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $this->dataItemHandler ) );

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->store->setConnectionManager( $connectionManager );
	}

	public function tearDown() {
		SemanticDataLookup::clear();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SemanticDataLookup::class,
			new SemanticDataLookup( $this->store )
		);
	}

	public function testInitLookupCache() {

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->initLookupCache( 42, DIWikiPage::newFromText( 'Foo' ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\StubSemanticData',
			$instance->getSemanticDataById( 42 )
		);
	}

	public function testUninitializedGetSemanticDataByIdThrowsException() {

		$instance = new SemanticDataLookup(
			$this->store
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->getSemanticDataById( 42 );
	}

	public function testSetLookupCache() {

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $propertyTable ] ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->setLookupCache( 42, $semanticData );
	}

	public function testGetSemanticDataFromTable() {

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
			->method( 'select' )
			->will( $this->returnValue( [ $row ] ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$semanticData = $instance->getSemanticDataFromTable(
			42,
			$subject,
			$propertyTable
		);

		$this->assertSame(
			$semanticData,
			$instance->getSemanticDataById( 42 )
		);
	}

	public function testGetSemanticDataFromTable_WithConstraint() {

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
			->method( 'select' )
			->with(
				$this->equalTo( ' INNER JOIN  AS p ON p_id=p.smw_id' ),
				$this->equalTo( 'p.smw_title as prop,fooField AS v0,  AS v2' ),
				$this->equalTo( "s_id='42' AND p.smw_iw!=':smw' AND p.smw_iw!=':smw-delete' AND p_id='9999'" ),
				$this->anything(),
				$this->equalTo( [ 'LIMIT' => 4 ] ) )
			->will( $this->returnValue( [ $row ] ) );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$requestOptions = new RequestOptions();
		$requestOptions->conditionConstraint = true;
		$requestOptions->setLimit( 4 );

		$requestOptions = $instance->findConditionConstraint(
			$propertyTable,
			$property,
			$requestOptions
		);

		$instance->getSemanticDataFromTable( 42, $subject, $propertyTable, $requestOptions );
	}

	public function testFetchSemanticData_NoDataItem() {

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
			->method( 'select' )
			->with(
				$this->equalTo( ' INNER JOIN  AS p ON p_id=p.smw_id' ),
				$this->equalTo( 'p.smw_title as prop,fooField AS v0,  AS v2' ),
				$this->equalTo( "s_id='42' AND p.smw_iw!=':smw' AND p.smw_iw!=':smw-delete'" ),
				$this->anything(),
				$this->equalTo( [] ) )
			->will( $this->returnValue( [ $row ] ) );

		$instance = new SemanticDataLookup(
			$this->store
		);

		$instance->fetchSemanticData( 42, null, $propertyTable );
	}

}
