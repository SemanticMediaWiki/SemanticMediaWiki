<?php

namespace SMW\Tests\Unit\Maintenance;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use Onoi\MessageReporter\SpyMessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\WikiPage;
use SMW\Iterators\ResultIterator;
use SMW\Maintenance\DataRebuilder;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Options;
use SMW\Query\QueryResult;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;
use TypeError;

/**
 * @covers \SMW\Maintenance\DataRebuilder
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class DataRebuilderTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	protected $obLevel;
	private $connectionManager;
	private JobFactory $jobFactory;

	// The Store writes to the output buffer during drop/setupStore, to avoid
	// inappropriate buffer settings which can cause interference during unit
	// testing, we clean the output buffer
	protected function setUp(): void {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$resultIterator = $this->getMockBuilder( ResultIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$resultIterator->expects( $this->any() )
			->method( 'count' )
			->willReturn( 0 );

		$entityIdDisposerJob = $this->getMockBuilder( EntityIdDisposerJob::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->willReturn( $resultIterator );

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newByNamespaceInvalidEntitiesResultIterator' )
			->willReturn( $resultIterator );

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->willReturn( $resultIterator );

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->willReturn( $resultIterator );

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->setMethods( [ 'newUpdateJob', 'newEntityIdDisposerJob' ] )
			->getMock();

		$this->jobFactory->expects( $this->any() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$this->jobFactory->expects( $this->any() )
			->method( 'newEntityIdDisposerJob' )
			->willReturn( $entityIdDisposerJob );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder() );

		$this->connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->obLevel = ob_get_level();
		ob_start();

		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DataRebuilder::class,
			new DataRebuilder( $store, $titleFactory, $this->jobFactory )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllWithoutOptions() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->once() )
			->method( 'rebuild' )
			->willReturnCallback( [ $this, 'refreshDataOnMockCallback' ] );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		// Needs an end otherwise phpunit is caught up in an infinite loop
		$instance->setOptions( new Options( [
			'e' => 1
		] ) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllForwardsUseJobOptionToDispatcher() {
		$capturedOptions = [];

		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->once() )
			->method( 'rebuild' )
			->willReturnCallback( [ $this, 'refreshDataOnMockCallback' ] );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$rebuilder->expects( $this->once() )
			->method( 'setOptions' )
			->willReturnCallback( static function ( $options ) use ( &$capturedOptions ) {
				$capturedOptions = $options;
			} );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			'e' => 1,
			'use-job' => true
		] ) );

		$this->assertTrue( $instance->rebuild() );
		$this->assertTrue( $capturedOptions['use-job'] );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testUseJobRebuildReportsRunJobsCommand() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->once() )
			->method( 'rebuild' )
			->willReturnCallback( [ $this, 'refreshDataOnMockCallback' ] );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$spyMessageReporter = new SpyMessageReporter();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );
		$instance->setMessageReporter( $spyMessageReporter );

		$instance->setOptions( new Options( [
			'e' => 1,
			'use-job' => true
		] ) );

		$this->assertTrue( $instance->rebuild() );

		$this->assertStringContainsString(
			'runJobs.php --type smw.update --procs',
			$spyMessageReporter->getMessagesAsString()
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllWithFullDelete() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->atLeastOnce() )
			->method( 'rebuild' )
			->willReturnCallback( [ $this, 'refreshDataOnMockCallback' ] );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [
				'refreshData',
				'drop' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->expects( $this->once() )
			->method( 'drop' );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			'e' => 1,
			'f' => true,
			'verbose' => false
		] ) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllWithStopRangeOption() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->exactly( 6 ) )
			->method( 'rebuild' )
			->willReturnCallback( [ $this, 'refreshDataOnMockCallback' ] );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			's' => 2,
			'n' => 5,
			'verbose' => false
		] ) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildSelectedPagesWithQueryOption() {
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( Title::newFromText( __METHOD__ ) );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [ $subject ] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$callCount = 0;
		$store->expects( $this->exactly( 2 ) )
			->method( 'getQueryResult' )
			->willReturnCallback( static function () use ( &$callCount, $queryResult ) {
				$callCount++;
				return $callCount === 1 ? 1 : $queryResult;
			} );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			'query' => '[[Category:Foo]]'
		] ) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testUseJobWithQuerySelectionReportsInlineNotice() {
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( Title::newFromText( __METHOD__ ) );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [ $subject ] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$callCount = 0;
		$store->expects( $this->exactly( 2 ) )
			->method( 'getQueryResult' )
			->willReturnCallback( static function () use ( &$callCount, $queryResult ) {
				$callCount++;
				return $callCount === 1 ? 1 : $queryResult;
			} );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$spyMessageReporter = new SpyMessageReporter();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );
		$instance->setMessageReporter( $spyMessageReporter );

		$instance->setOptions( new Options( [
			'query' => '[[Category:Foo]]',
			'use-job' => true
		] ) );

		$this->assertTrue( $instance->rebuild() );

		$this->assertStringContainsString(
			'--use-job applies to full rebuilds only',
			$spyMessageReporter->getMessagesAsString()
		);
	}

	public function testRebuildSelectedPagesWithCategoryNamespaceFilter() {
		$row = new stdClass;
		$row->cat_title = 'Foo';

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$database->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $row, &$whereConditions, &$capturedSelects, &$capturedTables ) {
					return $this->createMockSelectQueryBuilder( [ $row ], $whereConditions, $capturedSelects, $capturedTables );
				}
			);

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			'categories' => true
		] ) );

		$this->assertTrue( $instance->rebuild() );
		$this->assertSame( [ 'category' ], $capturedTables );
	}

	public function testRebuildSelectedPagesWithPropertyNamespaceFilter() {
		$row = new stdClass;
		$row->page_namespace = SMW_NS_PROPERTY;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$database->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $row, &$whereConditions, &$capturedSelects, &$capturedTables ) {
					return $this->createMockSelectQueryBuilder( [ $row ], $whereConditions, $capturedSelects, $capturedTables );
				}
			);

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			'p' => true
		] ) );

		$this->assertTrue( $instance->rebuild() );
		$this->assertSame( [ 'page' ], $capturedTables );
		$this->assertContains( [ 'page_namespace' => SMW_NS_PROPERTY ], $whereConditions );
	}

	public function testRebuildSelectedPagesWithPageOption() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$mwTitleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$titleFactory->expects( $this->exactly( 4 ) )
			->method( 'newFromText' )
			->willReturnCallback( static function ( $title ) use ( $mwTitleFactory ) {
				if ( $title === 'Help:Main page' ) {
					return $mwTitleFactory->newFromText( 'Main page', NS_HELP );
				}
				return $mwTitleFactory->newFromText( $title );
			} );

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			'page'  => 'Main page|Some other page|Help:Main page|Main page'
		] ) );

		$this->assertTrue( $instance->rebuild() );

		$this->assertEquals(
			3,
			$instance->getRebuildCount()
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllWithIgnoreExceptionsContinuesPastError() {
		$seenIds = [];

		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		// Mirror Rebuilder::rebuild() throwing from a job before next_position()
		// runs: the id is left unadvanced when the call throws. A PHP Error
		// (TypeError) is used because that is what escapes a catch ( Exception )
		// and aborts the whole rebuild (#6218). The safety net stops the loop
		// after far more iterations than the [1,3] range needs, so a regression
		// that fails to advance the id fails the assertion below instead of
		// looping forever.
		$rebuilder->expects( $this->any() )
			->method( 'rebuild' )
			->willReturnCallback( static function ( &$id ) use ( &$seenIds ) {
				$seenIds[] = $id;

				if ( count( $seenIds ) > 20 ) {
					$id = -1;
					return 1;
				}

				throw new TypeError( 'Return value must be of type string, array returned' );
			} );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$exceptionLogDir = sys_get_temp_dir() . '/smw-6218-' . uniqid();
		mkdir( $exceptionLogDir );

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );

		$instance->setOptions( new Options( [
			's' => 1,
			'e' => 3,
			'ignore-exceptions' => true,
			'exception-log' => $exceptionLogDir
		] ) );

		try {
			$this->assertTrue( $instance->rebuild() );

			// Each failing id in the [1,3] range is visited exactly once and the
			// run advances past it, proving the loop does not reprocess the same
			// id forever.
			$this->assertSame( [ 1, 2, 3 ], $seenIds );
			$this->assertSame( 3, $instance->getExceptionCount() );
		} finally {
			array_map( 'unlink', glob( "$exceptionLogDir/*" ) ?: [] );
			rmdir( $exceptionLogDir );
		}
	}

	public function testRebuildAllReportsFailedIdsAndCompletedWithErrors() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		// Every id in the [1,3] range fails, mirroring a run dominated by
		// per-entity errors under --ignore-exceptions.
		$rebuilder->expects( $this->any() )
			->method( 'rebuild' )
			->willReturnCallback( static function ( &$id ) {
				throw new TypeError( 'Return value must be of type string, array returned' );
			} );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$exceptionLogDir = sys_get_temp_dir() . '/smw-6975-' . uniqid();
		mkdir( $exceptionLogDir );

		$spyMessageReporter = new SpyMessageReporter();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );
		$instance->setMessageReporter( $spyMessageReporter );

		$instance->setOptions( new Options( [
			's' => 1,
			'e' => 3,
			'ignore-exceptions' => true,
			'exception-log' => $exceptionLogDir
		] ) );

		try {
			$instance->rebuild();

			$output = $spyMessageReporter->getMessagesAsString();

			// The data-step summary must surface the failures and must not claim
			// a plain successful completion.
			$this->assertStringContainsString( 'failed (IDs)', $output );
			$this->assertStringContainsString( 'completed with errors', $output );
			$this->assertSame( 3, $instance->getExceptionCount() );
		} finally {
			array_map( 'unlink', glob( "$exceptionLogDir/*" ) ?: [] );
			rmdir( $exceptionLogDir );
		}
	}

	public function testRebuildAllCleanRunReportsDone() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		// Every id processes cleanly; the dispatcher advances the id.
		$rebuilder->expects( $this->any() )
			->method( 'rebuild' )
			->willReturnCallback( static function ( &$id ) {
				$id++;
				return 1;
			} );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$spyMessageReporter = new SpyMessageReporter();

		$instance = new DataRebuilder( $store, $titleFactory, $this->jobFactory );
		$instance->setMessageReporter( $spyMessageReporter );

		$instance->setOptions( new Options( [ 's' => 1, 'e' => 3 ] ) );

		$this->assertTrue( $instance->rebuild() );

		$output = $spyMessageReporter->getMessagesAsString();

		// A clean run keeps the plain success wording and no failure lines.
		$this->assertStringContainsString( '... done.', $output );
		$this->assertStringNotContainsString( 'completed with errors', $output );
		$this->assertStringNotContainsString( 'failed (IDs)', $output );
		$this->assertSame( 0, $instance->getExceptionCount() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAll_SkipDispose_DoesNotRunDisposer() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->once() )
			->method( 'rebuild' )
			->willReturnCallback( [ $this, 'refreshDataOnMockCallback' ] );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		// The disposer is built via jobFactory->newEntityIdDisposerJob(); with
		// --skip-dispose set it must never be constructed.
		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->setMethods( [ 'newUpdateJob', 'newEntityIdDisposerJob' ] )
			->getMock();

		$jobFactory->expects( $this->any() )
			->method( 'newUpdateJob' )
			->willReturn(
				$this->getMockBuilder( UpdateJob::class )
					->disableOriginalConstructor()
					->getMock()
			);

		$jobFactory->expects( $this->never() )
			->method( 'newEntityIdDisposerJob' );

		$instance = new DataRebuilder( $store, $titleFactory, $jobFactory );

		$instance->setOptions( new Options( [
			'e' => 1,
			'skip-dispose' => true
		] ) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAll_Default_RunsDisposer() {
		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->once() )
			->method( 'rebuild' )
			->willReturnCallback( [ $this, 'refreshDataOnMockCallback' ] );

		$rebuilder->expects( $this->any() )
			->method( 'getMaxId' )
			->willReturn( 1000 );

		$rebuilder->expects( $this->any() )
			->method( 'getDispatchedEntities' )
			->willReturn( [] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		// Without --skip-dispose the disposer is built via
		// jobFactory->newEntityIdDisposerJob() and run.
		$entityIdDisposerJob = $this->getMockBuilder( EntityIdDisposerJob::class )
			->disableOriginalConstructor()
			->getMock();

		$resultIterator = $this->getMockBuilder( ResultIterator::class )
			->disableOriginalConstructor()
			->getMock();

		$resultIterator->expects( $this->any() )
			->method( 'count' )
			->willReturn( 0 );

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newOutdatedEntitiesResultIterator' )
			->willReturn( $resultIterator );

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newByNamespaceInvalidEntitiesResultIterator' )
			->willReturn( $resultIterator );

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newOutdatedQueryLinksResultIterator' )
			->willReturn( $resultIterator );

		$entityIdDisposerJob->expects( $this->any() )
			->method( 'newUnassignedQueryLinksResultIterator' )
			->willReturn( $resultIterator );

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->setMethods( [ 'newUpdateJob', 'newEntityIdDisposerJob' ] )
			->getMock();

		$jobFactory->expects( $this->any() )
			->method( 'newUpdateJob' )
			->willReturn(
				$this->getMockBuilder( UpdateJob::class )
					->disableOriginalConstructor()
					->getMock()
			);

		$jobFactory->expects( $this->once() )
			->method( 'newEntityIdDisposerJob' )
			->willReturn( $entityIdDisposerJob );

		$instance = new DataRebuilder( $store, $titleFactory, $jobFactory );

		$instance->setOptions( new Options( [
			'e' => 1
		] ) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @see Store::refreshData
	 */
	public function refreshDataOnMockCallback( &$index ) {
		$index++;
		return 1;
	}

}
