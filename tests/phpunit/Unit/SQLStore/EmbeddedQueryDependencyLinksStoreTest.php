<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\EmbeddedQueryDependencyLinksStore;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\EmbeddedQueryDependencyLinksStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EmbeddedQueryDependencyLinksStoreTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EmbeddedQueryDependencyLinksStore',
			new EmbeddedQueryDependencyLinksStore( $store )
		);
	}

	public function testPruneOutdatedTargetLinks() {

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );

		$this->assertTrue(
			$instance->pruneOutdatedTargetLinks( $compositePropertyTableDiffIterator )
		);
	}

	public function testPruneOutdatedTargetLinksBeingDisabled() {

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->setEnabledState( false );

		$this->assertNull(
			$instance->pruneOutdatedTargetLinks( $compositePropertyTableDiffIterator )
		);
	}

	public function testBuildParserCachePurgeJobParameters() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->any() )
			->method( 'getCombinedIdListOfChangedEntities' )
			->will( $this->returnValue( array( 1, 2 ) ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );

		$this->assertEquals(
			array( 'idlist' => array( 1, 2 ) ),
			$instance->buildParserCachePurgeJobParametersFrom( $compositePropertyTableDiffIterator )
		);
	}

	public function testBuildParserCachePurgeJobParametersBeingDisabled() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->setEnabledState( false );

		$this->assertEmpty(
			$instance->buildParserCachePurgeJobParametersFrom( $compositePropertyTableDiffIterator )
		);
	}

	public function testFindPartialEmbeddedQueryTargetLinksHashListFor() {

		$row = new \stdClass;
		$row->s_id = 1001;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getDataItemPoolHashListFor' ) )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getDataItemPoolHashListFor' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->anything(),
				$this->equalTo( array( 'o_id' => array( 42 ) ) ) )
			->will( $this->returnValue( array( $row ) ) );

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );

		$instance->findPartialEmbeddedQueryTargetLinksHashListFor( array( 42 ), 1, 200 );
	}

	public function testTryToaddDependencyListWhileBeingDisabled() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->setEnabledState( false );

		$embeddedQueryDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\EmbeddedQueryDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertNull(
			$instance->addDependencyList( $embeddedQueryDependencyListResolver )
		);
	}

	public function testTryToAddDependenciesForWhenDependencyListReturnsEmpty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->onConsecutiveCalls( 42, 1001 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->setEnabledState( true );

		$embeddedQueryDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\EmbeddedQueryDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$embeddedQueryDependencyListResolver->expects( $this->once() )
			->method( 'getQueryDependencySubjectList' )
			->will( $this->returnValue( array() ) );

		$embeddedQueryDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$this->assertNull(
			$instance->addDependencyList( $embeddedQueryDependencyListResolver )
		);
	}

	public function testAddDependenciesFromQueryResult() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->onConsecutiveCalls( 42, 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( array( 's_id' => 42 ) ) );

		$insert[] = array(
			's_id' => 42,
			'o_id' => 1001
		);

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( $insert ) );

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getPropertyValues' ) )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$embeddedQueryDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\EmbeddedQueryDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$embeddedQueryDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ )  ) );

		$embeddedQueryDependencyListResolver->expects( $this->any() )
			->method( 'getQueryDependencySubjectList' )
			->will( $this->returnValue( array( DIWikiPage::newFromText( 'Foo' ) ) ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->addDependencyList( $embeddedQueryDependencyListResolver );
	}

	public function testAddDependenciesFromQueryResullWhereObjectIdIsYetUnknownWhichRequiresToCreateTheIdOnTheFly() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID', 'makeSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->onConsecutiveCalls( 42, 0 ) );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( array( 's_id' => 42 ) ) );

		$insert[] = array(
			's_id' => 42,
			'o_id' => 1001
		);

		$connection->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( \SMWSQLStore3::QUERY_LINKS_TABLE ),
				$this->equalTo( $insert ) );

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getPropertyValues' ) )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$embeddedQueryDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\EmbeddedQueryDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$embeddedQueryDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ )  ) );

		$embeddedQueryDependencyListResolver->expects( $this->any() )
			->method( 'getQueryDependencySubjectList' )
			->will( $this->returnValue( array( DIWikiPage::newFromText( 'Foo', NS_CATEGORY ) ) ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->addDependencyList( $embeddedQueryDependencyListResolver );
	}

	public function testTryToAddDependenciesWithinSkewedTime() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getTouched' )
			->will( $this->returnValue( wfTimestamp( TS_MW ) + 10 ) );

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->once() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$embeddedQueryDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\EmbeddedQueryDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$embeddedQueryDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$instance = new EmbeddedQueryDependencyLinksStore( $store );
		$instance->addDependencyList( $embeddedQueryDependencyListResolver );
	}

}
