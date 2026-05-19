<?php

namespace SMW\Tests\Unit\SQLStore\QueryDependency;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\NamespaceExaminer;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\RequestOptions;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\QueryDependency\QueryDependencyLinksStore
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class QueryDependencyLinksStoreTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $store;
	private $spyLogger;
	private $namespaceExaminer;
	private $subject;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->store->setLogger(
			$this->spyLogger
		);

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$this->subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->subject->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$this->subject->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		$this->testEnvironment->clearPendingDeferredUpdates();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			QueryDependencyLinksStore::class,
			new QueryDependencyLinksStore( $queryResultDependencyListResolver, $dependencyLinksTableUpdater, $this->namespaceExaminer )
		);
	}

	public function testPruneOutdatedTargetLinks() {
		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$changeOp = $this->getMockBuilder( ChangeOp::class )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( $this->subject );

		$changeOp->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertTrue(
			$instance->pruneOutdatedTargetLinks( $changeOp )
		);
	}

	public function testPruneOutdatedTargetLinksBeingDisabled() {
		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$changeOp = $this->getMockBuilder( ChangeOp::class )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $this->subject );

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setEnabled( false );

		$this->assertNull(
			$instance->pruneOutdatedTargetLinks( $changeOp )
		);
	}

	public function testFindEmbeddedQueryTargetLinksHashListFrom() {
		$row = new stdClass;
		$row->s_id = 1001;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemsFromList' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getDataItemsFromList' );

		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$connection->method( 'addQuotes' )->willReturnCallback(
			static fn ( $v ) => "'" . $v . "'"
		);

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 1 );
		$requestOptions->setOffset( 200 );

		$instance->findDependencyTargetLinks( [ 42 ], $requestOptions );

		$this->assertSame( [ [ 'o_id' => [ 42 ] ] ], $capturedWheres );
	}

	public function testFindEmbeddedQueryTargetLinksHashListBySubject() {
		$row = new stdClass;
		$row->s_id = 1001;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getDataItemsFromList' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'getDataItemsFromList' );

		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$connection->method( 'addQuotes' )->willReturnCallback(
			static fn ( $v ) => "'" . $v . "'"
		);

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
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
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( 1 );
		$requestOptions->setOffset( 200 );

		$instance->findDependencyTargetLinksForSubject( WikiPage::newFromText( 'Foo' ), $requestOptions );

		$this->assertSame( [ [ 'o_id' => [ 42 ] ] ], $capturedWheres );
	}

	public function testCountDependencies() {
		$row = new stdClass;
		$row->count = 1001;

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->countDependencies( 42 );

		$this->assertSame( [ [ 'o_id' => [ 42 ] ] ], $capturedWheres );
	}

	public function testTryDoUpdateDependenciesByWhileBeingDisabled() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
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
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setEnabled( false );
		$queryResult = '';

		$instance->updateDependencies( $queryResult );
	}

	public function testUpdateDependencies_ExcludedRequestAction() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
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
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setEnabled( true );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getOption' )
			->with(	'request.action' )
			->willReturn( 'parse' );

		$queryResult = $this->getMockBuilder( QueryResult::class )
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
			->setMethods( [ 'getId' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42, 1001 );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
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
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setEnabled( true );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->willReturn( $this->subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testDisabledDependenciesUpdateOnNotSupportedNamespace() {
		$namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$dependencyLinksTableUpdater->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $store );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListByLateRetrievalFrom' );

		$queryResultDependencyListResolver->expects( $this->never() )
			->method( 'getDependencyListFrom' );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater,
			$namespaceExaminer
		);

		$instance->setEnabled( true );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->willReturn( $this->subject );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );
	}

	public function testdoUpdateDependenciesByFromQueryResult() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->once() )
			->method( 'getTouched' )
			->willReturn( 10 );

		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$subject->expects( $this->any() )
			->method( 'getHash' )
			->willReturn( 'Foo' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder() );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues' ] )
			->getMock();

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListFrom' )
			->willReturn( [ null, WikiPage::newFromText( __METHOD__ ) ] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
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
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->willReturn( $subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );

		$this->testEnvironment->executePendingDeferredUpdates();
	}

	public function testdoUpdateDependenciesByFromQueryResultWhereObjectIdIsYetUnknownWhichRequiresToCreateTheIdOnTheFly() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListFrom' )
			->willReturn( [ WikiPage::newFromText( 'Foo', NS_CATEGORY ) ] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
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
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->willReturn( $this->subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( QueryResult::class )
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
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->atLeastOnce() )
			->method( 'getHash' )
			->willReturn( 'Foo###' );

		$subject->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		// isRegistered() only checks `$row !== false`, so returning any
		// non-empty row preserves the "already registered, skip update"
		// branch this test was originally exercising.
		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( [ (object)[ 's_id' => 1 ] ] ) );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->setConnectionManager( $connectionManager );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResultDependencyListResolver->expects( $this->any() )
			->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
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
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->any() )
			->method( 'getContextPage' )
			->willReturn( $subject );

		$query->expects( $this->any() )
			->method( 'getLimit' )
			->willReturn( 1 );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getQuery' )
			->willReturn( $query );

		$instance->updateDependencies( $queryResult );
	}

	public function titleProvider() {
		$title = $this->getMockBuilder( Title::class )
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

		$title = $this->getMockBuilder( Title::class )
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
