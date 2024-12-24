<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\DataItemFactory;
use SMW\MediaWiki\JobFactory;
use SMW\SQLStore\QueryEngine\Fulltext\TextChangeUpdater;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\TextChangeUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextChangeUpdaterTest extends \PHPUnit\Framework\TestCase {

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

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory = $this->getMockBuilder( '\SMW\MediaWiki\JobFactory' )
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

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
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

		$searchTable = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTable' )
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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->willReturn( [ '42' ] );

		$changeOp->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( $dataItem );

		$nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->once() )
			->method( 'newFulltextSearchTableUpdateJob' )
			->with(
				$this->anything(),
				[ 'slot:id' => 'Foo#0##' ] )
			->willReturn( $nullJob );

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

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->willReturn( [] );

		$changeDiff->expects( $this->once() )
			->method( 'getTextItems' )
			->willReturn( [] );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'newChangeDiff' )
			->willReturn( null );

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
