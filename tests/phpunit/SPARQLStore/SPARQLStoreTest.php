<?php

namespace SMW\Tests\SPARQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SPARQLStore\SPARQLStore;
use SMW\Subobject;
use SMW\Tests\TestEnvironment;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;
use Title;

/**
 * @covers \SMW\SPARQLStore\SPARQLStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class SPARQLStoreTest extends \PHPUnit\Framework\TestCase {

	private $semanticDataFactory;

	protected function setUp(): void {
		parent::setup();

		$testEnvironment = new TestEnvironment();
		$this->semanticDataFactory = $testEnvironment->getUtilityFactory()->newSemanticDataFactory();
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
			->with( $subject )
			->willReturn( $semanticData );

		$instance = new SPARQLStore( $baseStore );

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$instance->getSemanticData( $subject )
		);
	}

	public function testDeleteSubjectOnMockBaseStore() {
		$title = Title::newFromText( 'DeleteSubjectOnMockBaseStore' );

		$expResource = Exporter::getInstance()->newExpElement( DIWikiPage::newFromTitle( $title ) );
		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expResource );

		$extraNamespaces = [
			$expResource->getNamespaceId() => $expResource->getNamespace()
		];

		$baseStore = $this->getMockBuilder( '\SMWStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$baseStore->expects( $this->once() )
			->method( 'deleteSubject' )
			->with( $title )
			->willReturn( true );

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase->expects( $this->once() )
			->method( 'deleteContentByValue' )
			->willReturn( true );

		$sparqlDatabase->expects( $this->once() )
			->method( 'delete' )
			->with(
				"{$resourceUri} ?p ?o",
				"{$resourceUri} ?p ?o",
				$extraNamespaces )
			->willReturn( true );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $sparqlDatabase );

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

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
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
			->willReturn( $repositoryResult );

		$sparqlDatabase->expects( $this->once() )
			->method( 'insertData' );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $sparqlDatabase );

		$instance = new SPARQLStore( $baseStore );
		$instance->setConnectionManager( $connectionManager );

		$instance->doSparqlDataUpdate( $semanticData );
	}

	public function testCallToChangeTitleForCompletePageMove() {
		$oldTitle = Title::newFromText( __METHOD__ . '-old' );
		$newTitle = Title::newFromText( __METHOD__ . '-new' );

		$respositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RespositoryConnection' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'insertDelete' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'changeTitle' );

		$instance = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->setConstructorArgs( [ $store ] )
			->onlyMethods( [ 'doSparqlDataDelete', 'getConnection' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $respositoryConnection );

		$instance->expects( $this->once() )
			->method( 'doSparqlDataDelete' )
			->with(	DIWikiPage::newFromTitle( $oldTitle ) );

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

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $repositoryResult );

		$connection->expects( $this->atLeastOnce() )
			->method( 'insertData' );

		$instance = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->onlyMethods( [ 'doSparqlDataDelete', 'getConnection' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'doSparqlDataDelete' )
			->with(	$expectedSubjectForDeleteTask );

		$instance->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance->doSparqlDataUpdate( $semanticData );
	}

	public function testDoSparqlDataUpdate_FailedPingThrowsException() {
		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'shouldPing' )
			->willReturn( true );

		$connection->expects( $this->once() )
			->method( 'ping' )
			->willReturn( false );

		$connection->expects( $this->never() )
			->method( 'insertData' );

		$instance = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->expectException( '\SMW\SPARQLStore\Exception\HttpEndpointConnectionException' );

		$instance->doSparqlDataUpdate( $semanticData );
	}

	public function testGetQueryResult() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getPrintrequests' )
			->willReturn( [] );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getDescription' )
			->willReturn( $description );

		$idLookup = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'warmUpCache' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->onlyMethods( [ 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idLookup );

		$repositoryClient = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryClient' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getRepositoryClient' )
			->willReturn( $repositoryClient );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SPARQLStore( $store );
		$instance->setConnectionManager( $connectionManager );

		$instance->getQueryResult( $query );
	}

	public function testGetQueryResultOnDisabledQueryEndpoint() {
		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getQueryResult' );

		$repositoryClient = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryClient' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryClient->expects( $this->atLeastOnce() )
			->method( 'getQueryEndpoint' )
			->willReturn( false );

		$connection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getRepositoryClient' )
			->willReturn( $repositoryClient );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new SPARQLStore( $store );
		$instance->setConnectionManager( $connectionManager );

		$instance->getQueryResult( $query );
	}

	public function testGetPropertyTableIdReferenceFinder() {
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getPropertyTableIdReferenceFinder' );

		$instance = new SPARQLStore( $store );
		$instance->getPropertyTableIdReferenceFinder();
	}

}
