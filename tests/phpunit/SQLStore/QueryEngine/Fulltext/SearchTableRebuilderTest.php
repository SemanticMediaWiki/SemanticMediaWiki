<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;
use SMWDataItem as DataItem;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableRebuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $searchTableUpdater;
	private $searchTable;
	private $connection;
	private $iteratorMockBuilder;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTable' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater->expects( $this->any() )
			->method( 'getSearchTable' )
			->willReturn( $this->searchTable );

		$this->iteratorMockBuilder = new IteratorMockBuilder();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder',
			new SearchTableRebuilder( $this->connection, $this->searchTableUpdater )
		);
	}

	public function testRebuildWithoutUpdate() {
		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getDiType' )
			->willReturn( DataItem::TYPE_BLOB );

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ $tableDefinition ] );

		$instance = new SearchTableRebuilder(
			$this->connection,
			$this->searchTableUpdater
		);

		$instance->rebuild();
	}

	public function testNeverRebuildOnOptimization() {
		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );

		$this->searchTableUpdater->expects( $this->never() )
			->method( 'getPropertyTables' );

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'optimize' );

		$instance = new SearchTableRebuilder(
			$this->connection,
			$this->searchTableUpdater
		);

		$instance->requestOptimization( true );

		$instance->rebuild();
	}

	public function testRebuildWithUpdateOnBlob() {
		$row = new \stdClass;
		$row->o_serialized = 'Foo';
		$row->o_blob = null;
		$row->s_id = 42;

		$resultWrapper = $this->iteratorMockBuilder->setClass( '\Wikimedia\Rdbms\ResultWrapper' )
			->with( [ $row ] )
			->incrementInvokedCounterBy( 1 )
			->getMockForIterator();

		$resultWrapper->expects( $this->atLeastOnce() )
			->method( 'numRows' )
			->willReturn( 1 );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $resultWrapper );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getDiType' )
			->willReturn( DataItem::TYPE_BLOB );

		$this->searchTable->expects( $this->any() )
			->method( 'isValidByType' )
			->willReturn( true );

		$this->searchTable->expects( $this->any() )
			->method( 'hasMinTokenLength' )
			->willReturn( true );

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ $tableDefinition ] );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'update' )
			->with( $row->s_id );

		$instance = new SearchTableRebuilder(
			$this->connection,
			$this->searchTableUpdater
		);

		$instance->reportVerbose( true );
		$instance->rebuild();
	}

	public function testgetQualifiedTableList() {
		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->atLeastOnce() )
			->method( 'getDiType' )
			->willReturn( DataItem::TYPE_BLOB );

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$instance = new SearchTableRebuilder(
			$this->connection,
			$this->searchTableUpdater
		);

		$this->assertIsArray(

			$instance->getQualifiedTableList()
		);
	}

	public function testRebuildByTable() {
		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDefinition->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$propertyTableDefinition->expects( $this->atLeastOnce() )
			->method( 'getDiType' )
			->willReturn( DataItem::TYPE_BLOB );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ $propertyTableDefinition ] );

		$instance = new SearchTableRebuilder(
			$this->connection,
			$this->searchTableUpdater
		);

		$instance->rebuildByTable( 'Foo' );
	}

}
