<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryDependency\QueryDependencyLinksStore
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class QueryDependencyLinksStoreTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		$this->testEnvironment->clearPendingDeferredUpdates();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $this->store ) );

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore',
			new QueryDependencyLinksStore( $dependencyLinksTableUpdater )
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

		$compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( array() ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

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

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->setEnabledState( false );

		$this->assertNull(
			$instance->pruneOutdatedTargetLinks( $compositePropertyTableDiffIterator )
		);
	}

	public function testBuildParserCachePurgeJobParametersOnBlacklistedProperty() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$entityIdListRelevanceDetectionFilter = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdListRelevanceDetectionFilter->expects( $this->once() )
			->method( 'getFilteredIdList' )
			->will( $this->returnValue( array( 1 ) ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$this->assertEquals(
			array( 'idlist' => array( 1 ) ),
			$instance->buildParserCachePurgeJobParametersFrom( $entityIdListRelevanceDetectionFilter )
		);
	}

	public function testBuildParserCachePurgeJobParametersBeingDisabled() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$entityIdListRelevanceDetectionFilter = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->setEnabledState( false );

		$this->assertEmpty(
			$instance->buildParserCachePurgeJobParametersFrom( $entityIdListRelevanceDetectionFilter )
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

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->findPartialEmbeddedQueryTargetLinksHashListFor( array( 42 ), 1, 200 );
	}

	public function testTryDoUpdateDependenciesByWhileBeingDisabled() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->setEnabledState( false );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListByLateRetrieval' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyList' )
			->will( $this->returnValue( array() ) );

		$instance->doUpdateDependenciesBy( $queryResultDependencyListResolver );
	}

	public function testTryDoUpdateDependenciesByForWhenDependencyListReturnsEmpty() {

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

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->setEnabledState( true );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrieval' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->once() )
			->method( 'getDependencyList' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$instance->doUpdateDependenciesBy( $queryResultDependencyListResolver );
	}

	public function testdoUpdateDependenciesByFromQueryResult() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->onConsecutiveCalls( 42, 1001 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getPropertyValues' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrieval' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ )  ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyList' )
			->will( $this->returnValue( array( null, DIWikiPage::newFromText( 'Foo' ) ) ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->once() )
			->method( 'addToUpdateList' )
			->with(
				$this->equalTo( 42 ),
				$this->anything() );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->doUpdateDependenciesBy( $queryResultDependencyListResolver );
	}

	public function testdoUpdateDependenciesByFromQueryResultWhereObjectIdIsYetUnknownWhichRequiresToCreateTheIdOnTheFly() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID', 'makeSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->onConsecutiveCalls( 42, 0 ) );

		$idTable->expects( $this->any() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 1001 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getPropertyValues' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrieval' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ )  ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyList' )
			->will( $this->returnValue( array( DIWikiPage::newFromText( 'Foo', NS_CATEGORY ) ) ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->once() )
			->method( 'addToUpdateList' )
			->with(
				$this->equalTo( 42 ),
				$this->anything() );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->doUpdateDependenciesBy( $queryResultDependencyListResolver );
	}

	public function testTryDoUpdateDependenciesByWithinSkewedTime() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getTouched' )
			->will( $this->returnValue( wfTimestamp( TS_MW ) + 60 ) );

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

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrieval' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$dependencyLinksTableUpdater
		);

		$instance->doUpdateDependenciesBy( $queryResultDependencyListResolver );
	}

}
