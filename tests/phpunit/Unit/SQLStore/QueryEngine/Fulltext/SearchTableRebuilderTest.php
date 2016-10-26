<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMWDataItem as DataItem;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableRebuilderTest extends \PHPUnit_Framework_TestCase {

	private $searchTableUpdater;
	private $connection;
	private $iteratorMockBuilder;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$this->iteratorMockBuilder = new IteratorMockBuilder();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder',
			new SearchTableRebuilder( $this->connection, $this->searchTableUpdater )
		);
	}

	public function testRunWithoutUpdate() {

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getDiType' )
			->will( $this->returnValue( DataItem::TYPE_BLOB ) );

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $tableDefinition ) ) );

		$instance = new SearchTableRebuilder(
			$this->connection,
			$this->searchTableUpdater
		);

		$instance->run();
	}

	public function testRunWithUpdateOnBlob() {

		$row = new \stdClass;
		$row->o_serialized = 'Foo';
		$row->s_id = 42;

		$resultWrapper = $this->iteratorMockBuilder->setClass( '\ResultWrapper' )
			->with( array( $row ) )
			->incrementInvokedCounterBy( 1 )
			->getMockForIterator();

		$resultWrapper->expects( $this->atLeastOnce() )
			->method( 'numRows' )
			->will( $this->returnValue( 1 ) );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getDiType' )
			->will( $this->returnValue( DataItem::TYPE_BLOB ) );

		$searchTable = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTable' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getSearchTable' )
			->will( $this->returnValue( $searchTable ) );

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $tableDefinition ) ) );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'update' )
			->with( $this->equalTo( $row->s_id ) );

		$instance = new SearchTableRebuilder(
			$this->connection,
			$this->searchTableUpdater
		);

		$instance->reportVerbose( true );
		$instance->run();
	}

}
