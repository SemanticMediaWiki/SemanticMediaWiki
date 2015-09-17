<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\RedirectLookup;
use SMW\InMemoryPoolCache;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Exporter\Escaper;

use SMWExpNsResource as ExpNsResource;
use SMWExpLiteral as ExpLiteral;
use SMWExpResource as ExpResource;
use SMWExporter as Exporter;

/**
 * @covers \SMW\SPARQLStore\RedirectLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RedirectLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RedirectLookup',
			new RedirectLookup( $repositoryConnection )
		);
	}

	public function testRedirectTragetForBlankNode() {

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $repositoryConnection );

		$expNsResource = new ExpNsResource( '', '', '', null );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTragetForDataItemWithSubobject() {

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $repositoryConnection );
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

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( false );

		$instance = new RedirectLookup( $repositoryConnection );
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

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( array( $expLiteral ) );

		$instance = new RedirectLookup( $repositoryConnection );
		$instance->reset();

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

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( array( $expLiteral, null ) );

		$instance = new RedirectLookup( $repositoryConnection );
		$instance->reset();

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
			Exporter::getInstance()->getNamespaceUri( 'property' ),
			'property',
			$propertyPage
		);

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( array( $resource, $resource ) );

		$instance = new RedirectLookup( $repositoryConnection );
		$instance->reset();

		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$targetResource = $instance->findRedirectTargetResource( $expNsResource, $exists );

		$this->assertNotSame(
			$expNsResource,
			$targetResource
		);

		$expectedResource = new ExpNsResource(
			Escaper::encodePage( $propertyPage ),
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
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

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( array( $expLiteral, $expLiteral ) );

		$instance = new RedirectLookup( $repositoryConnection );
		$instance->reset();

		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->setExpectedException( 'RuntimeException' );
		$instance->findRedirectTargetResource( $expNsResource, $exists );
	}

	public function testRedirectTargetForCachedLookup() {

		$dataItem = new DIWikiPage( 'Foo', NS_MAIN );
		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );

		$poolCache = InMemoryPoolCache::getInstance()->getPoolCacheFor( 'sparql.store.redirectlookup' );

		$poolCache->save(
			$expNsResource->getUri(),
			$expNsResource
		);

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $repositoryConnection );

		$exists = null;

		$instance->findRedirectTargetResource( $expNsResource, $exists );

		$this->assertTrue( $exists );
		$instance->reset();
	}

	/**
	 * @dataProvider nonRedirectableResourceProvider
	 */
	public function testRedirectTargetForNonRedirectableResource( $expNsResource ) {

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectLookup( $repositoryConnection );
		$instance->reset();

		$exists = null;

		$instance->findRedirectTargetResource( $expNsResource, $exists );
		$instance->reset();

		$this->assertFalse( $exists );
	}

	private function createRepositoryConnectionMockToUse( $listReturnValue ) {

		$repositoryResult = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\RepositoryResult' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryResult->expects( $this->once() )
			->method( 'current' )
			->will( $this->returnValue( $listReturnValue ) );

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryConnection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $repositoryResult ) );

		return $repositoryConnection;
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
			Exporter::getInstance()->getSpecialPropertyResource( '_MDAT', true )
		);

		return $provider;
	}

}
