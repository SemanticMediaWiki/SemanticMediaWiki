<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\TextChangeUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\TextChangeUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TextChangeUpdaterTest extends TestCase {

	private $dataItemFactory;
	private $connection;
	private $searchTableUpdater;
	private $cache;
	private JobFactory $jobFactory;
	private $logger;
	private $testEnvironment;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->logger = TestEnvironment::newSpyLogger();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater = $this->getMockBuilder( SearchTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobFactory', $this->jobFactory );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TextChangeUpdater::class,
			new TextChangeUpdater( $this->connection, $this->cache, $this->searchTableUpdater )
		);
	}

	public function testPushUpdatesOnNullChange() {
		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->willReturn( true );

		$changeDiff = $this->getMockBuilder( ChangeDiff::class )
			->disableOriginalConstructor()
			->getMock();

		$changeOp = $this->getMockBuilder( ChangeOp::class )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->willReturn( [] );

		$changeOp->expects( $this->never() )
			->method( 'getSubject' );

		$instance = new TextChangeUpdater(
			$this->connection,
			$this->cache,
			$this->searchTableUpdater
		);

		$instance->setLogger(
			$this->logger
		);

		$instance->pushUpdates(
			$changeOp
		);
	}

	public function testPushUpdates() {
		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$searchTable = $this->getMockBuilder( SearchTable::class )
			->disableOriginalConstructor()
			->getMock();

		$searchTable->expects( $this->atLeastOnce() )
			->method( 'getDataItemById' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'Bar', SMW_NS_PROPERTY ) );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->willReturn( true );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getSearchTable' )
			->willReturn( $searchTable );

		$changeOp = $this->getMockBuilder( ChangeOp::class )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->willReturn( [ '42' ] );

		$changeOp->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( $dataItem );

		$fulltextJob = $this->getMockBuilder( FulltextSearchTableUpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->once() )
			->method( 'newFulltextSearchTableUpdateJob' )
			->with(
				$this->anything(),
				[ 'slot:id' => 'Foo#0##' ] )
			->willReturn( $fulltextJob );

		$instance = new TextChangeUpdater(
			$this->connection,
			$this->cache,
			$this->searchTableUpdater
		);

		$instance->setLogger(
			$this->logger
		);

		$instance->pushUpdates(
			$changeOp
		);
	}

	public function testPushUpdatesDirectlyWhenExecutedFromCommandLine() {
		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->willReturn( true );

		$changeDiff = $this->getMockBuilder( ChangeDiff::class )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$changeDiff->expects( $this->once() )
			->method( 'getTextItems' )
			->willReturn( [] );

		$changeOp = $this->getMockBuilder( ChangeOp::class )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'newChangeDiff' )
			->willReturn( $changeDiff );

		$changeOp->expects( $this->never() )
			->method( 'getSubject' );

		$instance = new TextChangeUpdater(
			$this->connection,
			$this->cache,
			$this->searchTableUpdater
		);

		$instance->setLogger(
			$this->logger
		);

		$instance->isCommandLineMode( true );

		$instance->pushUpdates(
			$changeOp
		);
	}

	public function testNullUpdate() {
		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->willReturn( true );

		$changeDiff = $this->getMockBuilder( ChangeDiff::class )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$changeDiff->expects( $this->once() )
			->method( 'getTextItems' )
			->willReturn( [] );

		$changeOp = $this->getMockBuilder( ChangeOp::class )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'newChangeDiff' )
			->willReturn( $changeDiff );

		$changeOp->expects( $this->never() )
			->method( 'getSubject' );

		$instance = new TextChangeUpdater(
			$this->connection,
			$this->cache,
			$this->searchTableUpdater
		);

		$instance->setLogger(
			$this->logger
		);

		$instance->isCommandLineMode( true );

		$instance->pushUpdates(
			$changeOp
		);
	}

}
