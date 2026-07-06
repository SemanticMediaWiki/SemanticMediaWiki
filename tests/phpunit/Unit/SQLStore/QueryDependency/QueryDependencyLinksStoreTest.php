<?php

namespace SMW\Tests\Unit\SQLStore\QueryDependency;

use MediaWiki\Deferred\DeferredUpdates;
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
use Wikimedia\ScopedCallback;

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

	public function testUpdateDependenciesCoalescesFlushAcrossCallbacksForSameSid() {
		// Two `#ask` queries on the same page that share a query-id hash
		// (same conditions, different printouts: `Query::getHash()` deliberately
		// excludes printouts, see Query.php:587-589) both resolve to the same
		// `$sid` in `smw_query_links`. The per-callback `doUpdate()` flush did
		// a DELETE-then-INSERT keyed on `s_id`, so the second callback's write
		// overwrote the first, dropping its printout-derived dependencies. The
		// flush is now deferred so both callbacks' `addToUpdateList` invocations
		// accumulate in the static `$updateList` and a single later flush writes
		// the union.
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$title->method( 'exists' )->willReturn( true );
		$title->method( 'getTouched' )->willReturn( 10 );

		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();
		$subject->method( 'getTitle' )->willReturn( $title );
		$subject->method( 'getHash' )->willReturn( 'SharedHashSubject' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder() );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();
		$connectionManager->method( 'getConnection' )->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues' ] )
			->getMock();
		$store->setConnectionManager( $connectionManager );
		$store->method( 'getPropertyValues' )->willReturn( [] );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();
		$queryResultDependencyListResolver->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );
		$queryResultDependencyListResolver->method( 'getDependencyListFrom' )
			->willReturnOnConsecutiveCalls(
				[ WikiPage::newFromText( 'PrintoutAlpha', SMW_NS_PROPERTY ) ],
				[ WikiPage::newFromText( 'PrintoutBeta', SMW_NS_PROPERTY ) ]
			);

		$callOrder = [];

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();
		$dependencyLinksTableUpdater->method( 'getId' )->willReturn( 42 );
		$dependencyLinksTableUpdater->method( 'addToUpdateList' )
			->willReturnCallback( static function () use ( &$callOrder ): void {
				$callOrder[] = 'add';
			} );
		$dependencyLinksTableUpdater->method( 'doUpdate' )
			->willReturnCallback( static function () use ( &$callOrder ): void {
				$callOrder[] = 'flush';
			} );
		$dependencyLinksTableUpdater->method( 'getStore' )->willReturn( $store );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);
		$instance->setLogger( $this->spyLogger );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();
		$query->method( 'getContextPage' )->willReturn( $subject );
		$query->method( 'getLimit' )->willReturn( 1 );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();
		$queryResult->method( 'getQuery' )->willReturn( $query );

		// Suppress opportunistic deferred-update execution so the two
		// `updateDependencies()` calls both land in the buffer before the
		// flush. Without this guard, `DeferredUpdates::addUpdate()` may run
		// pending updates synchronously in the test scope, which would drain
		// the buffer between the two calls and produce per-callback flushes,
		// hiding the bug the fix targets.
		$scope = DeferredUpdates::preventOpportunisticUpdates();

		$instance->updateDependencies( $queryResult );
		$instance->updateDependencies( $queryResult );

		ScopedCallback::consume( $scope );

		$this->testEnvironment->executePendingDeferredUpdates();

		$flushIndex = array_search( 'flush', $callOrder, true );
		$this->assertNotFalse( $flushIndex, 'doUpdate must be called at least once via the deferred flush' );

		$addIndices = array_keys( $callOrder, 'add', true );

		// Four `add` calls (`addToUpdateList` runs twice per invocation: once
		// for `$dependencyList`, once for `$dependencyListByLateRetrieval`).
		// Pre-fix the `CallableUpdate` fingerprint dedup at `pushUpdate`
		// would short-circuit the second invocation entirely, leaving only
		// two `add` calls and the original "first wins" data-loss shape.
		// This assertion is the regression sentinel against the dedup
		// re-appearing at this layer.
		$this->assertCount( 4, $addIndices,
			'Both invocations must reach addToUpdateList twice each; got: ' . json_encode( $callOrder ) );
		$this->assertLessThan( $flushIndex, max( $addIndices ),
			'Every addToUpdateList call must precede the first doUpdate flush so dep lists accumulate in $updateList before the DELETE-then-INSERT write; got: ' . json_encode( $callOrder ) );

		// At most one `flush` for a single-request batch — the `$flushScheduled`
		// guard keeps the second registration from queueing a redundant drain.
		$flushCount = count( array_keys( $callOrder, 'flush', true ) );
		$this->assertSame( 1, $flushCount,
			'Exactly one flush should run per request; got: ' . json_encode( $callOrder ) );
	}

	public function testUpdateDependenciesCoalescesFlushAcrossDistinctSids() {
		// Two `#ask` queries on the same page with different conditions
		// (different `$hash` -> different `$sid`) should both register
		// dependencies, and both batches should flush in a single
		// `DependencyLinksTableUpdater::doUpdate()` call. The batched
		// flush path must not be `$sid`-specific.
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$title->method( 'exists' )->willReturn( true );
		$title->method( 'getTouched' )->willReturn( 10 );

		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();
		$subject->method( 'getTitle' )->willReturn( $title );
		$subject->method( 'getHash' )->willReturn( 'SubjectWithTwoDistinctQueries' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder() );

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();
		$connectionManager->method( 'getConnection' )->willReturn( $connection );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues' ] )
			->getMock();
		$store->setConnectionManager( $connectionManager );
		$store->method( 'getPropertyValues' )->willReturn( [] );

		$queryResultDependencyListResolver = $this->getMockBuilder( QueryResultDependencyListResolver::class )
			->disableOriginalConstructor()
			->getMock();
		$queryResultDependencyListResolver->method( 'getDependencyListByLateRetrievalFrom' )
			->willReturn( [] );
		$queryResultDependencyListResolver->method( 'getDependencyListFrom' )
			->willReturnOnConsecutiveCalls(
				[ WikiPage::newFromText( 'DistinctA', SMW_NS_PROPERTY ) ],
				[ WikiPage::newFromText( 'DistinctB', SMW_NS_PROPERTY ) ]
			);

		$flushCount = 0;

		$dependencyLinksTableUpdater = $this->getMockBuilder( DependencyLinksTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();
		$dependencyLinksTableUpdater->method( 'getId' )
			->willReturnOnConsecutiveCalls( 100, 200 );
		$dependencyLinksTableUpdater->method( 'doUpdate' )
			->willReturnCallback( static function () use ( &$flushCount ): void {
				$flushCount++;
			} );
		$dependencyLinksTableUpdater->method( 'getStore' )->willReturn( $store );

		$instance = new QueryDependencyLinksStore(
			$queryResultDependencyListResolver,
			$dependencyLinksTableUpdater,
			$this->namespaceExaminer
		);
		$instance->setLogger( $this->spyLogger );

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();
		$query->method( 'getContextPage' )->willReturn( $subject );
		$query->method( 'getLimit' )->willReturn( 1 );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();
		$queryResult->method( 'getQuery' )->willReturn( $query );

		$scope = DeferredUpdates::preventOpportunisticUpdates();

		$instance->updateDependencies( $queryResult );
		$instance->updateDependencies( $queryResult );

		ScopedCallback::consume( $scope );

		$this->testEnvironment->executePendingDeferredUpdates();

		// One effective flush, even though both registrations queue their own
		// drain — the second drain finds the buffer empty (cleared by the
		// first drain's drain-then-clear-then-iterate ordering) and skips
		// the redundant write.
		$this->assertSame( 1, $flushCount,
			'Two updateDependencies invocations must produce exactly one effective doUpdate flush per request' );
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
