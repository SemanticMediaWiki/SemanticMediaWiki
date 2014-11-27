<?php

namespace SMW\Tests\SPARQLStore;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SPARQLStore\SPARQLStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SemanticData;
use SMW\Subobject;

use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

use Title;

/**
 * @covers \SMW\SPARQLStore\SPARQLStore
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class SPARQLStoreTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;

	protected function setUp() {
		parent::setup();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\SPARQLStore',
			new SPARQLStore()
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMW\SPARQLStore\SPARQLStore',
			new \SMWSPARQLStore()
		);
	}

	public function testGetSemanticDataOnMockBaseStore() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$baseStore = $this->getMockBuilder( '\SMWStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$baseStore->expects( $this->once() )
			->method( 'getSemanticData' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( $semanticData ) );

		$instance = new SPARQLStore( $baseStore );

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$instance->getSemanticData( $subject )
		);
	}

	public function testDeleteSubjectOnMockBaseStore() {

		$title = Title::newFromText( 'DeleteSubjectOnMockBaseStore' );

		$expResource = Exporter::getDataItemExpElement( DIWikiPage::newFromTitle( $title ) );
		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expResource );

		$extraNamespaces = array(
			$expResource->getNamespaceId() => $expResource->getNamespace()
		);

		$baseStore = $this->getMockBuilder( '\SMWStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$baseStore->expects( $this->once() )
			->method( 'deleteSubject' )
			->with( $this->equalTo( $title ) )
			->will( $this->returnValue( true ) );

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase->expects( $this->once() )
			->method( 'deleteContentByValue' )
			->will( $this->returnValue( true ) );

		$sparqlDatabase->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( "{$resourceUri} ?p ?o" ),
				$this->equalTo( "{$resourceUri} ?p ?o" ),
				$this->equalTo( $extraNamespaces ) )
			->will( $this->returnValue( true ) );

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $sparqlDatabase ) );

		$instance = new SPARQLStore( $baseStore );
		$instance->setConnectionManager( $connectionManager );

		$instance->deleteSubject( $title );
	}

	public function testDoSparqlDataUpdateOnMockBaseStore() {

		$semanticData = new SemanticData( new DIWikiPage( __METHOD__, NS_MAIN ) );

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'Foo' ),
			$semanticData->getSubject()
		);

		$listReturnValue = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$baseStore = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( $listReturnValue ) );

		$sparqlDatabase->expects( $this->once() )
			->method( 'insertData' );

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $sparqlDatabase ) );

		$instance = new SPARQLStore( $baseStore );
		$instance->setConnectionManager( $connectionManager );

		$instance->doSparqlDataUpdate( $semanticData );
	}

	public function testCallToChangeTitleForCompletePageMove() {

		$oldTitle = Title::newFromText( __METHOD__ . '-old' );
		$newTitle = Title::newFromText( __METHOD__ . '-new' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'changeTitle' );

		$instance = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->setConstructorArgs( array( $store ) )
			->setMethods( array( 'doSparqlDataDelete', 'insertDelete' ) )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'doSparqlDataDelete' )
			->with(	$this->equalTo( DIWikiPage::newFromTitle( $oldTitle ) ) );

		$instance->changeTitle( $oldTitle, $newTitle, 42, 0 );
	}

	public function testNoDeleteTaskForSubobjectsDuringUpdate() {

		$expectedSubjectForDeleteTask = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$subobject = new Subobject( $expectedSubjectForDeleteTask->getTitle() );
		$subobject->setEmptyContainerForId( 'Foo' );

		$semanticData = $this->semanticDataFactory
			->setSubject( $expectedSubjectForDeleteTask )
			->newEmptySemanticData();

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$federateResultSet = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\GenericHttpDatabaseConnector' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( $federateResultSet ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'insertData' );

		$instance = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'doSparqlDataDelete', 'getConnection' ) )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'doSparqlDataDelete' )
			->with(	$this->equalTo( $expectedSubjectForDeleteTask ) );

		$instance->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance->doSparqlDataUpdate( $semanticData );
	}

	public function testGetQueryResult() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getPrintrequests' )
			->will( $this->returnValue( array() ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\GenericHttpDatabaseConnector' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SPARQLStore( $store );
		$instance->setConnectionManager( $connectionManager );

		$instance->getQueryResult( $query );
	}

}
