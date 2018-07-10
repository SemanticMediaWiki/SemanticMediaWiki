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

	protected function setUp() {

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

		$this->slot = '';
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TextChangeUpdater::class,
			new TextChangeUpdater( $this->connection, $this->cache, $this->searchTableUpdater )
		);
	}

	public function testPushUpdatesOnDisabledDeferredUpdateAndNullChange() {

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

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TextChangeUpdater(
			$this->connection,
			$this->cache,
			$this->searchTableUpdater
		);

		$instance->setLogger(
			$this->logger
		);

		$instance->asDeferredUpdate( false );

		$instance->pushUpdates(
			$changeOp,
			$deferredRequestDispatchManager
		);
	}

	public function testPushUpdatesAsDeferredUpdate() {

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

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$deferredRequestDispatchManager->expects( $this->once() )
			->method( 'dispatchFulltextSearchTableUpdateJobWith' )
			->with(
				$this->anything(),
				$this->equalTo( [ 'slot:id' => 'Foo#0##' ] ) );

		$instance = new TextChangeUpdater(
			$this->connection,
			$this->cache,
			$this->searchTableUpdater
		);

		$instance->setLogger(
			$this->logger
		);

		$instance->asDeferredUpdate( true );

		$instance->pushUpdates(
			$changeOp,
			$deferredRequestDispatchManager
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

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TextChangeUpdater(
			$this->connection,
			$this->cache,
			$this->searchTableUpdater
		);

		$instance->setLogger(
			$this->logger
		);

		$instance->asDeferredUpdate( true );
		$instance->isCommandLineMode( true );

		$instance->pushUpdates(
			$changeOp,
			$deferredRequestDispatchManager
		);
	}

}
