<?php

namespace SMW\Tests\SPARQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Escaper;
use SMW\InMemoryPoolCache;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\RepositoryConnection;
use SMW\SPARQLStore\RepositoryRedirectLookup;

/**
 * @covers \SMW\SPARQLStore\RepositoryRedirectLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryRedirectLookupTest extends TestCase {

	public function testCanConstruct() {
		$repositoryConnection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			RepositoryRedirectLookup::class,
			new RepositoryRedirectLookup( $repositoryConnection )
		);
	}

	public function testRedirectTargetForBlankNode() {
		$repositoryConnection = $this->getMockBuilder( RepositoryConnection::class )
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
		$repositoryConnection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
		$dataItem = new WikiPage( 'Foo', 1, '', 'beingASubobject' );

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
		$dataItem = new WikiPage( 'Foo', 1, '', '' );

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

		$dataItem = new WikiPage( 'Foo', 1, '', '' );

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

		$dataItem = new WikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->assertSame(
			$expNsResource,
			$instance->findRedirectTargetResource( $expNsResource, $exists )
		);

		$this->assertTrue( $exists );
	}

	public function testRedirectTargetForDBLookupWithMultipleEntriesForcesNewResource() {
		$propertyPage = new WikiPage( 'Foo', SMW_NS_PROPERTY );

		$resource = new ExpNsResource(
			'Foo',
			Exporter::getInstance()->getNamespaceUri( 'property' ),
			'property',
			$propertyPage
		);

		$repositoryConnection = $this->createRepositoryConnectionMockToUse( [ $resource, $resource ] );

		$instance = new RepositoryRedirectLookup( $repositoryConnection );
		$instance->reset();

		$dataItem = new WikiPage( 'Foo', 1, '', '' );

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

		$dataItem = new WikiPage( 'Foo', 1, '', '' );

		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );
		$exists = null;

		$this->expectException( 'RuntimeException' );
		$instance->findRedirectTargetResource( $expNsResource, $exists );
	}

	public function testRedirectTargetForCachedLookup() {
		$dataItem = new WikiPage( 'Foo', NS_MAIN );
		$expNsResource = new ExpNsResource( 'Foo', 'Bar', '', $dataItem );

		$poolCache = InMemoryPoolCache::getInstance()->getPoolCacheById( RepositoryRedirectLookup::POOLCACHE_ID );

		$poolCache->save(
			$expNsResource->getUri(),
			$expNsResource
		);

		$repositoryConnection = $this->getMockBuilder( RepositoryConnection::class )
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
		$repositoryConnection = $this->getMockBuilder( RepositoryConnection::class )
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
		$repositoryResult = $this->getMockBuilder( RepositoryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$repositoryResult->expects( $this->once() )
			->method( 'current' )
			->willReturn( $listReturnValue );

		$repositoryConnection = $this->getMockBuilder( RepositoryConnection::class )
			->disableOriginalConstructor()
			->getMock();

		$repositoryConnection->expects( $this->once() )
			->method( 'select' )
			->willReturn( $repositoryResult );

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
