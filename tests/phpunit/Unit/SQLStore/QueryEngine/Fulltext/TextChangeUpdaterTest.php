<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\DataItemFactory;
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
class TextChangeUpdaterTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $connection;
	private $searchTableUpdater;
	private $cache;
	private $slot;
	private $logger;
	private $testEnvironment;

	protected function setUp() {

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

		$this->slot = '';
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
			->will( $this->returnValue( true ) );

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( [] ) );

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
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'Bar', SMW_NS_PROPERTY ) ) );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getSearchTable' )
			->will( $this->returnValue( $searchTable ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getChangedEntityIdSummaryList' )
			->will( $this->returnValue( [ '42' ] ) );

		$changeOp->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( $dataItem ) );

		$nullJob = $this->getMockBuilder( '\SMW\MediaWiki\Jobs\NullJob' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->once() )
			->method( 'newFulltextSearchTableUpdateJob' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'slot:id' => 'Foo#0##' ] ) )
			->will( $this->returnValue( $nullJob ) );

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
			->will( $this->returnValue( true ) );

		$changeDiff = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeDiff' )
			->disableOriginalConstructor()
			->getMock();

		$changeDiff->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( [] ) );

		$changeDiff->expects( $this->once() )
			->method( 'getTextItems' )
			->will( $this->returnValue( [] ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'newChangeDiff' )
			->will( $this->returnValue( $changeDiff ) );

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
			->will( $this->returnValue( true ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'newChangeDiff' )
			->will( $this->returnValue( null ) );

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
