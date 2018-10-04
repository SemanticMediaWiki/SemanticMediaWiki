<?php

namespace SMW\Tests\SPARQLStore;

use SMW\DIWikiPage;
use SMW\Exporter\Escaper;
use SMW\InMemoryPoolCache;
use SMW\SPARQLStore\RepositoryRedirectLookup;
use SMWExpLiteral as ExpLiteral;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\RepositoryRedirectLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryRedirectLookupTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryRedirectLookup',
			new RepositoryRedirectLookup( $repositoryConnection )
		);
	}

	public function testRedirectTargetForBlankNode() {

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RepositoryRedirectLookup( $repositoryConnection );

		$expNsResource = new ExpNsResource( '', '', '', null );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTargetForDataItemWithSubobject() {

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
		$dataItem = new DIWikiPage( 'Foo', 1, '', 'beingASubobject' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertTrue( $exists );
	}

	public function testRedirectTargetForDBLookupWithNoEntry() {

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( false );

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
		$dataItem = new DIWikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertFalse( $exists );
	}

	public function testRedirectTargetForDBLookupWithSingleEntry() {

		$expLiteral = new ExpLiteral( 'Redirect' );

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( [ $expLiteral ] );

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
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

	public function testRedirectTargetForDBLookupWithMultipleEntries() {

		$expLiteral = new ExpLiteral( 'Redirect' );

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( [ $expLiteral, null ] );

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
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

	public function testRedirectTargetForDBLookupWithMultipleEntriesForcesNewResource() {

		$propertyPage = new DIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$resource = new ExpNsResource(
			'Foo',
			Exporter::getInstance()->getNamespaceUri( 'property' ),
			'property',
			$propertyPage
		);

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( [ $resource, $resource ] );

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
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

	public function testRedirectTargetForDBLookupWithForNonMultipleResourceEntryThrowsException() {

		$expLiteral = new ExpLiteral( 'Redirect' );

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( [ $expLiteral, $expLiteral ] );

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
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

		$poolCache = InMemoryPoolCache::getInstance()->getPoolCacheById( RepositoryRedirectLookup::POOLCACHE_ID );

		$poolCache->save(
			$expNsResource->getUri(),
			$expNsResource
		);

		$repositoryConnection = $this->getMockBuilder( '\SMW\SPARQLStore\RepositoryConnection' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RepositoryRedirectLookup( $repositoryConnection );

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

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
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

		$provider[] = [
			Exporter::getInstance()->getSpecialPropertyResource( '_INST' )
		];

		$provider[] = [
			Exporter::getInstance()->getSpecialPropertyResource( '_SUBC' )
		];

		$provider[] = [
			Exporter::getInstance()->getSpecialPropertyResource( '_REDI' )
		];

		$provider[] = [
			Exporter::getInstance()->getSpecialPropertyResource( '_MDAT' )
		];

		$provider[] = [
			Exporter::getInstance()->getSpecialPropertyResource( '_MDAT', true )
		];

		return $provider;
	}

}
