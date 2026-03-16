<?php

namespace SMW\Tests\SPARQLStore;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Query\Language\Description;
use SMW\SemanticData;
use SMW\SPARQLStore\Exception\HttpEndpointConnectionException;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnection;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\SPARQLStore\RespositoryConnection;
use SMW\SPARQLStore\SPARQLStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Subobject;
use SMW\Tests\TestEnvironment;
use SMWExporter as Exporter;

/**
 * @covers \SMW\SPARQLStore\SPARQLStore
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class SPARQLStoreTest extends TestCase {

	private $semanticDataFactory;

	protected function setUp(): void {
		parent::setup();

		$testEnvironment = new TestEnvironment();
		$this->semanticDataFactory = $testEnvironment->getUtilityFactory()->newSemanticDataFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SPARQLStore::class,
			new SPARQLStore()
		);
	}

	public function testGetSemanticDataOnMockBaseStore() {
		$subject = DIWikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$baseStore = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$baseStore->expects( $this->once() )
			->method( 'getSemanticData' )
			->with( $subject )
			->willReturn( $semanticData );

		$instance = new SPARQLStore( $baseStore );

		$this->assertInstanceOf(
			SemanticData::class,
			$instance->getSemanticData( $subject )
		);
	}

	public function testDeleteSubjectOnMockBaseStore() {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'DeleteSubjectOnMockBaseStore' );

		$expResource = Exporter::getInstance()->newExpElement( DIWikiPage::newFromTitle( $title ) );
		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expResource );

		$extraNamespaces = [
			$expResource->getNamespaceId() => $expResource->getNamespace()
		];

		$baseStore = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$baseStore->expects( $this->once() )
			->method( 'deleteSubject' )
			->with( $title )
			->willReturn( true );

		$sparqlDatabase = $this->getMockBuilder( GenericRepositoryConnector::class )
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

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
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

		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$baseStore = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$sparqlDatabase = $this->getMockBuilder( GenericRepositoryConnector::class )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $repositoryResult );

		$sparqlDatabase->expects( $this->once() )
			->method( 'insertData' );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
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
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$oldTitle = $titleFactory->newFromText( __METHOD__ . '-old' );
		$newTitle = $titleFactory->newFromText( __METHOD__ . '-new' );

		$respositoryConnection = $this->getMockBuilder( RespositoryConnection::class )
			->disableOriginalConstructor()
			->setMethods( [ 'insertDelete' ] )
			->getMock();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'changeTitle' );

		$instance = $this->getMockBuilder( SPARQLStore::class )
			->setConstructorArgs( [ $store ] )
			->setMethods( [ 'doSparqlDataDelete', 'getConnection' ] )
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
		$expectedSubjectForDeleteTask = DIWikiPage::newFromTitle( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );

		$subobject = new Subobject( $expectedSubjectForDeleteTask->getTitle() );
		$subobject->setEmptyContainerForId( 'Foo' );

		$semanticData = $this->semanticDataFactory
			->setSubject( $expectedSubjectForDeleteTask )
			->newEmptySemanticData();

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( GenericRepositoryConnector::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $repositoryResult );

		$connection->expects( $this->atLeastOnce() )
			->method( 'insertData' );

		$instance = $this->getMockBuilder( SPARQLStore::class )
			->setMethods( [ 'doSparqlDataDelete', 'getConnection' ] )
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
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( GenericRepositoryConnector::class )
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

		$instance = $this->getMockBuilder( SPARQLStore::class )
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$instance->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->expectException( HttpEndpointConnectionException::class );

		$instance->doSparqlDataUpdate( $semanticData );
	}

	public function testGetQueryResult() {
		$description = $this->getMockBuilder( Description::class )
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
			->setMethods( [ 'warmUpCache' ] )
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idLookup );

		$repositoryClient = $this->getMockBuilder( RepositoryClient::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getRepositoryClient' )
			->willReturn( $repositoryClient );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
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

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getQueryResult' );

		$repositoryClient = $this->getMockBuilder( RepositoryClient::class )
			->disableOriginalConstructor()
			->getMock();

		$repositoryClient->expects( $this->atLeastOnce() )
			->method( 'getQueryEndpoint' )
			->willReturn( false );

		$connection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'getRepositoryClient' )
			->willReturn( $repositoryClient );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
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
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getPropertyTableIdReferenceFinder' );

		$instance = new SPARQLStore( $store );
		$instance->getPropertyTableIdReferenceFinder();
	}

}
