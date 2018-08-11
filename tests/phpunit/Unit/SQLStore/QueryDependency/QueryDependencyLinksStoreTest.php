<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
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
	private $spyLogger;
	private $jobFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->setLogger(
			$this->spyLogger
		);

		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobFactory', $this->jobFactory );
		$this->testEnvironment->registerObject( 'NamespaceExaminer', $namespaceExaminer );
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

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore',
			new QueryDependencyLinksStore( $queryResultDependencyListResolver, $dependencyLinksTableUpdater )
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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( 'Foo' ) ) );

		$changeOp->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( array() ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertTrue(
			$instance->pruneOutdatedTargetLinks( $changeOp )
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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setEnabled( false );

		$this->assertNull(
			$instance->pruneOutdatedTargetLinks( $changeOp )
		);
	}

	public function testParserCachePurgeJobParametersOnBlacklistedProperty() {

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
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$entityIdListRelevanceDetectionFilter->expects( $this->once() )
			->method( 'getFilteredIdList' )
			->will( $this->returnValue( array( 1 ) ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$expected = [
			'idlist' => [ 1 ],
			'exec.mode' => 'exec.journal'
		];

		$nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->once() )
			->method( 'newParserCachePurgeJob' )
			->with(
				$this->anything(),
				$this->equalTo( $expected ) )
			->will( $this->returnValue( $nullJob ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->pushParserCachePurgeJob( $entityIdListRelevanceDetectionFilter );
	}

	public function testParserCachePurgeJobParametersBeingDisabled() {

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

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->never() )
			->method( 'newParserCachePurgeJob' );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setEnabled( false );

		$instance->pushParserCachePurgeJob( $entityIdListRelevanceDetectionFilter );
	}

	public function testParserCachePurgeJobParametersOnEmptyList() {

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

		$entityIdListRelevanceDetectionFilter->expects( $this->once() )
			->method( 'getFilteredIdList' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->never() )
			->method( 'newParserCachePurgeJob' );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setEnabled( true );

		$instance->pushParserCachePurgeJob( $entityIdListRelevanceDetectionFilter );
	}

	public function testFindEmbeddedQueryTargetLinksHashListFrom() {

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

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
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

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 1 );
		$requestOptions->setOffset( 200 );

		$instance->findDependencyTargetLinks( array( 42 ), $requestOptions );
	}

	public function testFindEmbeddedQueryTargetLinksHashListBySubject() {

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

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
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

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$dependencyLinksTableUpdater->expects( $this->once() )
			->method( 'getId' )
			->will( $this->returnValue( 42 ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 1 );
		$requestOptions->setOffset( 200 );

		$instance->findDependencyTargetLinksForSubject( DIWikiPage::newFromText( 'Foo' ), $requestOptions );
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

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListFrom' )
			->will( $this->returnValue( array() ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setEnabled( false );
		$queryResult = '';

		$instance->updateDependencies( $queryResult );
	}

	public function testUpdateDependencies_ExcludedRequestAction() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListFrom' )
			->will( $this->returnValue( array() ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setEnabled( true );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getOption' )
			->with(	$this->equalTo( 'request.action' ) )
			->will( $this->returnValue( 'parse' ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$this->assertNull(
			$instance->updateDependencies( $queryResult )
		);
	}

	public function testTryDoUpdateDependenciesByForWhenDependencyListReturnsEmpty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getId' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
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

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->once() )
			->method( 'getDependencyListFrom' )
			->will( $this->returnValue( array() ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setEnabled( true );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 1 ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$instance->updateDependencies( $queryResult );

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testDisabledDependenciesUpdateOnNotSupportedNamespace() {

		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

		$this->testEnvironment->registerObject( 'NamespaceExaminer', $namespaceExaminer );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListByLateRetrievalFrom' );

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListFrom' );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setEnabled( true );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$instance->updateDependencies( $queryResult );
	}

	public function testdoUpdateDependenciesByFromQueryResult() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getTouched' )
			->will( $this->returnValue( 10 ) );

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$subject->expects( $this->any() )
			->method( 'getHash' )
			->will( $this->returnValue( 'Foo' ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getPropertyValues' ) )
			->getMock();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListFrom' )
			->will( $this->returnValue( array( null, DIWikiPage::newFromText( __METHOD__ ) ) ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getId' )
			->will( $this->onConsecutiveCalls( 42, 1001 ) );

		$dependencyLinksTableUpdater->expects( $this->atLeastOnce() )
			->method( 'addToUpdateList' )
			->with(
				$this->equalTo( 1001 ),
				$this->anything() );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->will( $this->returnValue( $subject ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 1 ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$instance->updateDependencies( $queryResult );

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testdoUpdateDependenciesByFromQueryResultWhereObjectIdIsYetUnknownWhichRequiresToCreateTheIdOnTheFly() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->will( $this->returnValue( array() ) );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListFrom' )
			->will( $this->returnValue( array( DIWikiPage::newFromText( 'Foo', NS_CATEGORY ) ) ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getId' )
			->will( $this->onConsecutiveCalls( 0, 0 ) );

		$dependencyLinksTableUpdater->expects( $this->once() )
			->method( 'createId' )
			->will( $this->returnValue( 1001 ) );

		$dependencyLinksTableUpdater->expects( $this->atLeastOnce() )
			->method( 'addToUpdateList' )
			->with(
				$this->equalTo( 1001 ),
				$this->anything() );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 1 ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$instance->updateDependencies( $queryResult );

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testTryDoUpdateDependenciesByWithinSkewedTime( $title ) {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->atLeastOnce() )
			->method( 'getHash' )
			->will( $this->returnValue( 'Foo###' ) );

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->will( $this->returnValue( array() ) );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( 4 ) );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->will( $this->returnValue( $store ) );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->will( $this->returnValue( $subject ) );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 1 ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->will( $this->returnValue( $query ) );

		$instance->updateDependencies( $queryResult );
	}

	public function titleProvider() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getTouched' )
			->will( $this->returnValue( wfTimestamp( TS_MW ) + 60 ) );

		$provider[] = array(
			$title
		);

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		// This should be a `once` but it failed on PHP: hhvm-3.18 DB=sqlite; MW=master; PHPUNIT=5.7.*
		// with "Method was expected to be called 1 times, actually called 0 times." 
		$title->expects( $this->any() )
			->method( 'getTouched' )
			->will( $this->returnValue( '2017-06-15 08:36:55+00' ) );

		$provider[] = array(
			$title
		);

		$provider[] = array(
			null
		);

		return $provider;
	}

}
