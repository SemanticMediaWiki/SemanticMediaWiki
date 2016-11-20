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
	private $transitionalDiffStore;
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

		$this->transitionalDiffStore = $this->getMockBuilder( '\SMW\SQLStore\TransitionalDiffStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->slot = '';
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\TextByChangeUpdater',
			new TextByChangeUpdater( $this->connection, $this->searchTableUpdater, $this->textSanitizer, $this->transitionalDiffStore )
		);
	}

	public function testPushUpdatesOnDisabledDeferredUpdateAndNullChange() {

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( array() ) );

		$compositePropertyTableDiffIterator->expects( $this->never() )
			->method( 'getSubject' );

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TextByChangeUpdater(
			$this->connection,
			$this->searchTableUpdater,
			$this->textSanitizer,
			$this->transitionalDiffStore
		);

		$instance->asDeferredUpdate( false );

		$instance->pushUpdates(
			$compositePropertyTableDiffIterator,
			$deferredRequestDispatchManager,
			$this->slot
		);
	}

	public function testPushUpdatesAsDeferredUpdate() {

		$this->slot = 42;

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ) );

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$deferredRequestDispatchManager->expects( $this->once() )
			->method( 'scheduleSearchTableUpdateJobWith' )
			->with(
				$this->anything(),
				$this->equalTo( array( 'slot:id' => $this->slot ) ) );

		$instance = new TextByChangeUpdater(
			$this->connection,
			$this->searchTableUpdater,
			$this->textSanitizer,
			$this->transitionalDiffStore
		);

		$instance->asDeferredUpdate( true );

		$instance->pushUpdates(
			$compositePropertyTableDiffIterator,
			$deferredRequestDispatchManager,
			$this->slot
		);
	}

	public function testPushUpdatesDirectlyWhenExecutedFromCommandLine() {

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$compositePropertyTableDiffIterator->expects( $this->once() )
			->method( 'getTableChangeOps' )
			->will( $this->returnValue( array() ) );

		$compositePropertyTableDiffIterator->expects( $this->never() )
			->method( 'getSubject' );

		$deferredRequestDispatchManager = $this->getMockBuilder( '\SMW\DeferredRequestDispatchManager' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TextByChangeUpdater(
			$this->connection,
			$this->searchTableUpdater,
			$this->textSanitizer,
			$this->transitionalDiffStore
		);

		$instance->asDeferredUpdate( true );
		$instance->isCommandLineMode( true );

		$instance->pushUpdates(
			$compositePropertyTableDiffIterator,
			$deferredRequestDispatchManager,
			$this->slot
		);
	}

}
