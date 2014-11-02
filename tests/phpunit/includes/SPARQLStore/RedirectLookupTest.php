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
		$instance->clear();

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
		$instance->clear();

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
		$instance->clear();

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
		$instance->clear();

		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->setExpectedException( 'RuntimeException' );
		$instance->findRedirectTargetResource( $expNsResource, $exists );
	}

	public function testRedirectTargetForCachedLookup() {

		$dataItem = new DIWikiPage( 'Foo', NS_MAIN );
		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );

		$cache = $this->getMockBuilder( '\SMW\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->equalTo( $expNsResource->getUri() ) )
			->will( $this->returnValue( $expNsResource ) );

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $connection, $cache );

		$exists = null;

		$instance->findRedirectTargetResource( $expNsResource, $exists );

		$this->assertTrue( $exists );
		$instance->clear();
	}

	/**
	 * @dataProvider nonRedirectableResourceProvider
	 */
	public function testRedirectTargetForNonRedirectableResource( $expNsResource ) {

		$cache = $this->getMockBuilder( '\SMW\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->never() )
			->method( 'contains' );

		$connection = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $connection, $cache );

		$exists = null;

		$instance->findRedirectTargetResource( $expNsResource, $exists );

		$this->assertFalse( $exists );
		$instance->clear();
	}

	private function createMockSparqlDatabaseFor( $listReturnValue ) {

		$federateResultSet = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\FederateResultSet' )
			->disableOriginalConstructor()
			->getMock();

		$federateResultSet->expects( $this->once() )
			->method( 'current' )
			->will( $this->returnValue( $listReturnValue ) );

		$sparqlDatabase = $this->getMockBuilder( '\SMWSparqlDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$sparqlDatabase->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $federateResultSet ) );

		return $sparqlDatabase;
	}

	public function nonRedirectableResourceProvider() {

		$provider[] = array(
			Exporter::getInstance()->getSpecialPropertyResource( '_INST' )
		);

		$provider[] = array(
			Exporter::getInstance()->getSpecialPropertyResource( '_SUBC' )
		);

		$provider[] = array(
			Exporter::getInstance()->getSpecialPropertyResource( '_REDI' )
		);

		$provider[] = array(
			Exporter::getInstance()->getSpecialPropertyResource( '_MDAT' )
		);

		$provider[] = array(
			Exporter::getInstance()->getResourceElementForProperty( new DIProperty( 'Foo' ), true )
		);

		return $provider;
	}

}
