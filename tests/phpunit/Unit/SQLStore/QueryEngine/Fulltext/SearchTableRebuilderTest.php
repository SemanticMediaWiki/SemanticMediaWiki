<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine\Fulltext;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;
use stdClass;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableRebuilderTest extends TestCase {

	private $searchTableUpdater;
	private $searchTable;
	private $connection;
	private $iteratorMockBuilder;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable = $this->getMockBuilder( SearchTable::class )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater = $this->getMockBuilder( SearchTableUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTableUpdater->expects( $this->any() )
			->method( 'getSearchTable' )
			->willReturn( $this->searchTable );

		$this->iteratorMockBuilder = new IteratorMockBuilder();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SearchTableRebuilder::class,
			new SearchTableRebuilder( $this->connection, $this->searchTableUpdater )
		);
	}

	public function testRebuildWithoutUpdate() {
		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$row = new stdClass;
		$row->o_serialized = 'Foo';
		$row->o_blob = null;
		$row->s_id = 42;

		$resultWrapper = $this->iteratorMockBuilder->setClass( ResultWrapper::class )
			->with( [ $row ] )
			->incrementInvokedCounterBy( 1 )
			->getMockForIterator();

		$resultWrapper->expects( $this->atLeastOnce() )
			->method( 'numRows' )
			->willReturn( 1 );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $resultWrapper );

		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'smw_di_blob' );

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
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
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
		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
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
