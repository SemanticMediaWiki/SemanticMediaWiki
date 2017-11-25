<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\TextByChangeUpdater;
use SMW\DataItemFactory;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\TextByChangeUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextByChangeUpdaterTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $connection;
	private $searchTableUpdater;
	private $textSanitizer;
	private $tempChangeOpStore;
	private $slot;

	protected function setUp() {

		$this->dataItemFactory = new DataItemFactory();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$this->textSanitizer = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->tempChangeOpStore = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\TempChangeOpStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->slot = '';
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\TextByChangeUpdater',
			new TextByChangeUpdater( $this->connection, $this->searchTableUpdater, $this->textSanitizer, $this->tempChangeOpStore )
		);
	}

	public function testPushUpdatesOnDisabledDeferredUpdateAndNullChange() {

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( array() ) );

		$changeOp->expects( $this->once() )
			->method( 'getDataOps' )
			->will( $this->returnValue( array() ) );

		$changeOp->expects( $this->never() )
			->method( 'getSubject' );

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TextByChangeUpdater(
			$this->connection,
			$this->searchTableUpdater,
			$this->textSanitizer,
			$this->tempChangeOpStore
		);

		$instance->asDeferredUpdate( false );

		$instance->pushUpdates(
			$changeOp,
			$deferredRequestDispatchManager
		);
	}

	public function testPushUpdatesAsDeferredUpdate() {

		$dataItem = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$this->tempChangeOpStore->expects( $this->once() )
			->method( 'createSlotFrom' )
			->will( $this->returnValue( 42 ) );

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
			->will( $this->returnValue( array( '42' ) ) );

		$changeOp->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ) );

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$deferredRequestDispatchManager->expects( $this->once() )
			->method( 'dispatchFulltextSearchTableUpdateJobWith' )
			->with(
				$this->anything(),
				$this->equalTo( array( 'slot:id' => 42 ) ) );

		$instance = new TextByChangeUpdater(
			$this->connection,
			$this->searchTableUpdater,
			$this->textSanitizer,
			$this->tempChangeOpStore
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

		$changeOp = $this->getMockBuilder( '\SMW\SQLStore\ChangeOp\ChangeOp' )
			->disableOriginalConstructor()
			->getMock();

		$changeOp->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( array() ) );

		$changeOp->expects( $this->once() )
			->method( 'getDataOps' )
			->will( $this->returnValue( array() ) );

		$changeOp->expects( $this->never() )
			->method( 'getSubject' );

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TextByChangeUpdater(
			$this->connection,
			$this->searchTableUpdater,
			$this->textSanitizer,
			$this->tempChangeOpStore
		);

		$instance->asDeferredUpdate( true );
		$instance->isCommandLineMode( true );

		$instance->pushUpdates(
			$changeOp,
			$deferredRequestDispatchManager
		);
	}

}
