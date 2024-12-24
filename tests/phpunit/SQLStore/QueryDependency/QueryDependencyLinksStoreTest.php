<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryDependency\QueryDependencyLinksStore
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class QueryDependencyLinksStoreTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $spyLogger;
	private $jobFactory;
	private $subject;
	private $testEnvironment;

	protected function setUp(): void {
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
			->willReturn( true );

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$this->subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->subject->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$this->subject->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->testEnvironment->registerObject( 'JobFactory', $this->jobFactory );
		$this->testEnvironment->registerObject( 'NamespaceExaminer', $namespaceExaminer );
		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
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
			->willReturn( $this->store );

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
			->willReturn( $propertyTableInfoFetcher );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( $this->subject );

		$changeOp->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

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
			->willReturn( $propertyTableInfoFetcher );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $this->subject );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

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

	public function testFindEmbeddedQueryTargetLinksHashListFrom() {
		$row = new \stdClass;
		$row->s_id = 1001;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getDataItemPoolHashListFor' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getDataItemPoolHashListFor' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				\SMWSQLStore3::QUERY_LINKS_TABLE,
				$this->anything(),
				[ 'o_id' => [ 42 ] ] )
			->willReturn( [ $row ] );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

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

		$instance->findDependencyTargetLinks( [ 42 ], $requestOptions );
	}

	public function testFindEmbeddedQueryTargetLinksHashListBySubject() {
		$row = new \stdClass;
		$row->s_id = 1001;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getDataItemPoolHashListFor' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getDataItemPoolHashListFor' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				\SMWSQLStore3::QUERY_LINKS_TABLE,
				$this->anything(),
				[ 'o_id' => [ 42 ] ] )
			->willReturn( [ $row ] );

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$dependencyLinksTableUpdater->expects( $this->once() )
			->method( 'getId' )
			->willReturn( 42 );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 1 );
		$requestOptions->setOffset( 200 );

		$instance->findDependencyTargetLinksForSubject( DIWikiPage::newFromText( 'Foo' ), $requestOptions );
	}

	public function testCountDependencies() {
		$row = new \stdClass;
		$row->count = 1001;

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				\SMWSQLStore3::QUERY_LINKS_TABLE,
				$this->anything(),
				[ 'o_id' => [ 42 ] ] )
			->willReturn( $row );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater
		);

		$instance->countDependencies( 42 );
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
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListFrom' )
			->willReturn( [] );

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
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListFrom' )
			->willReturn( [] );

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
			->with(	'request.action' )
			->willReturn( 'parse' );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$this->assertNull(
			$instance->updateDependencies( $queryResult )
		);
	}

	public function testTryDoUpdateDependenciesByForWhenDependencyListReturnsEmpty() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42, 1001 );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$queryResultDependencyListResolver->expects( $this->once() )
			->method( 'getDependencyListFrom' )
			->willReturn( [] );

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
			->willReturn( $this->subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testDisabledDependenciesUpdateOnNotSupportedNamespace() {
		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$this->testEnvironment->registerObject( 'NamespaceExaminer', $namespaceExaminer );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

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
			->willReturn( $this->subject );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );
	}

	public function testdoUpdateDependenciesByFromQueryResult() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->once() )
			->method( 'getTouched' )
			->willReturn( 10 );

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$subject->expects( $this->any() )
			->method( 'getHash' )
			->willReturn( 'Foo' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyValues' ] )
			->getMock();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListFrom' )
			->willReturn( [ null, DIWikiPage::newFromText( __METHOD__ ) ] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42, 1001 );

		$dependencyLinksTableUpdater->expects( $this->atLeastOnce() )
			->method( 'addToUpdateList' )
			->with(
				1001,
				$this->anything() );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

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
			->willReturn( $subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testdoUpdateDependenciesByFromQueryResultWhereObjectIdIsYetUnknownWhichRequiresToCreateTheIdOnTheFly() {
		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListFrom' )
			->willReturn( [ DIWikiPage::newFromText( 'Foo', NS_CATEGORY ) ] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 0, 0 );

		$dependencyLinksTableUpdater->expects( $this->once() )
			->method( 'createId' )
			->willReturn( 1001 );

		$dependencyLinksTableUpdater->expects( $this->atLeastOnce() )
			->method( 'addToUpdateList' )
			->with(
				1001,
				$this->anything() );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

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
			->willReturn( $this->subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

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
			->willReturn( 'Foo###' );

		$subject->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$queryResultDependencyListResolver = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver' )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getId' )
			->willReturn( 4 );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

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
			->willReturn( $subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );
	}

	public function titleProvider() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->once() )
			->method( 'getTouched' )
			->willReturn( wfTimestamp( TS_MW ) + 60 );

		$provider[] = [
			$title
		];

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		// This should be a `once` but it failed on PHP: hhvm-3.18 DB=sqlite; MW=master; PHPUNIT=5.7.*
		// with "Method was expected to be called 1 times, actually called 0 times."
		$title->expects( $this->any() )
			->method( 'getTouched' )
			->willReturn( '2017-06-15 08:36:55+00' );

		$provider[] = [
			$title
		];

		return $provider;
	}

}
