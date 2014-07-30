<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\RedirectLookup;
use SMW\DIWikiPage;
use SMW\DIProperty;

use SMWExpNsResource as ExpNsResource;
use SMWExpLiteral as ExpLiteral;
use SMWExpResource as ExpResource;
use SMWExporter as Exporter;

/**
 * @covers \SMW\SPARQLStore\RedirectLookup
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RedirectLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RedirectLookup',
			new RedirectLookup( $sparqlDatabase )
		);
	}

	public function testRedirectTragetForBlankNode() {

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $sparqlDatabase );

		$expNsResource = new ExpNsResource( '', '', '', null );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTragetForDataItemWithSubobject() {

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $sparqlDatabase );
		$dataItem = new DIWikiPage( 'Foo', 1, '', 'beingASubobject' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertTrue( $exists );
	}

	public function testRedirectTragetForDBLookupWithNoEntry() {

		$sparqlDatabase = $this->createMockSparqlDatabaseFor( false );

		$instance = new RedirectLookup( $sparqlDatabase );
		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTragetForDBLookupWithSingleEntry() {

		$expLiteral = new ExpLiteral( 'Redirect' );

		$sparqlDatabase = $this->createMockSparqlDatabaseFor( array( $expLiteral ) );

		$instance = new RedirectLookup( $sparqlDatabase );
		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertTrue( $exists );
	}

	public function testRedirectTragetForDBLookupWithMultipleEntries() {

		$expLiteral = new ExpLiteral( 'Redirect' );

		$sparqlDatabase = $this->createMockSparqlDatabaseFor( array( $expLiteral, null ) );

		$instance = new RedirectLookup( $sparqlDatabase );
		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertTrue( $exists );
	}

	public function testRedirectTragetForDBLookupWithMultipleEntriesForcesNewResource() {

		$propertyPage = new DIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$resource = new ExpNsResource(
			'Foo',
			Exporter::getNamespaceUri( 'property' ),
			'property',
			$propertyPage
		);

		$sparqlDatabase = $this->createMockSparqlDatabaseFor( array( $resource, $resource ) );

		$instance = new RedirectLookup( $sparqlDatabase );
		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$targetResource = $instance->findRedirectTargetResource( $expNsResource, $exists );

		$this->assertNotSame(
			$expNsResource,
			$targetResource
		);

		$expectedResource = new ExpNsResource(
			Exporter::getInstance()->getEncodedPageName( $propertyPage ),
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki'
		);

		$this->assertEquals(
			$expectedResource,
			$targetResource
		);

		$this->assertTrue( $exists );
	}

	public function testRedirectTragetForDBLookupWithForNonMultipleResourceEntryThrowsException() {

		$expLiteral = new ExpLiteral( 'Redirect' );

		$sparqlDatabase = $this->createMockSparqlDatabaseFor( array( $expLiteral, $expLiteral ) );

		$instance = new RedirectLookup( $sparqlDatabase );
		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->setExpectedException( 'RuntimeException' );
		$instance->findRedirectTargetResource( $expNsResource, $exists );
	}

	private function createMockSparqlDatabaseFor( $listReturnValue ) {

		$federateResultList = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultList' )
			->disableOriginalConstructor()
			->getMock();

		$federateResultList->expects( $this->once() )
			->method( 'current' )
			->will( $this->returnValue( $listReturnValue ) );

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $federateResultList ) );

		return $sparqlDatabase;
	}

}
