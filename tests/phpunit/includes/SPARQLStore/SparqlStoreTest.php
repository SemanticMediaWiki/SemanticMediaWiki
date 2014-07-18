<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\SPARQLStore;

use SMW\DIWikiPage;
use SMW\SemanticData;

use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

use Title;

/**
 * @covers \SMW\SPARQLStore\SPARQLStore
 *
 * @ingroup Test
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

		$instance = new SPARQLStore( $baseStore );
		$instance->setSparqlDatabase( $sparqlDatabase );

		$instance->deleteSubject( $title );
	}

	public function testDoSparqlDataUpdateOnMockBaseStore() {

		$semanticData = new SemanticData( new DIWikiPage( 'Foo', NS_MAIN, '' ) );

		$listReturnValue = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultList' )
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

		$instance = new SPARQLStore( $baseStore );
		$instance->setSparqlDatabase( $sparqlDatabase );

		$instance->doSparqlDataUpdate( $semanticData );
	}

}
