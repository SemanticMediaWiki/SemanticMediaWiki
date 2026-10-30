<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine\Fulltext;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

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

	use MockSelectQueryBuilderTrait;

	private $searchTableUpdater;
	private $searchTable;
	private $connection;

	/**
	 * @var int[]
	 */
	private array $exemptionLookups = [];

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

		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ] );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

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

	public function testRebuildLooksUpExemptionOncePerProperty() {
		$this->recordExemptionLookups();

		$this->newRebuilderOverBlobRows( [ 1, 2, 1, 2, 1 ] )->rebuild();

		$this->assertSame( [ 1, 2 ], $this->exemptionLookups );
	}

	public function testChunkedRebuildLooksUpExemptionOncePerProperty() {
		$this->recordExemptionLookups();

		$this->newRebuilderOverBlobRows( [ 1, 2, 1, 2, 1 ] )->rebuildChunk();

		$this->assertSame( [ 1, 2 ], $this->exemptionLookups );
	}

	public function testRebuildSkipsOnlyRowsOfExemptedProperties() {
		$this->searchTable->method( 'isExemptedPropertyById' )
			->willReturnCallback( static fn ( $pid ) => (int)$pid === 1 );

		$updatedProperties = [];
		$this->searchTableUpdater->method( 'update' )
			->willReturnCallback( static function ( $sid, $pid ) use ( &$updatedProperties ) {
				$updatedProperties[] = (int)$pid;
			} );

		$this->newRebuilderOverBlobRows( [ 1, 2, 1, 2 ] )->rebuild();

		$this->assertSame( [ 2, 2 ], $updatedProperties );
	}

	private function recordExemptionLookups(): void {
		$this->searchTable->method( 'isExemptedPropertyById' )
			->willReturnCallback( function ( $pid ) {
				$this->exemptionLookups[] = (int)$pid;
				return false;
			} );
	}

	/**
	 * @param int[] $propertyIds One `smw_di_blob` row per entry, in this order
	 */
	private function newRebuilderOverBlobRows( array $propertyIds ): SearchTableRebuilder {
		$rows = [];

		foreach ( $propertyIds as $i => $propertyId ) {
			$row = new stdClass;
			$row->s_id = (string)( $i + 1 );
			$row->p_id = (string)$propertyId;
			$row->o_blob = 'Lorem ipsum';
			$row->o_hash = null;
			$rows[] = $row;
		}

		$this->connection->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $rows ) );

		$tableDefinition = $this->createMock( PropertyTableDefinition::class );
		$tableDefinition->method( 'getName' )->willReturn( 'smw_di_blob' );
		$tableDefinition->method( 'getDiType' )->willReturn( DataItem::TYPE_BLOB );

		$this->searchTable->method( 'isValidByType' )->willReturn( true );
		$this->searchTable->method( 'hasMinTokenLength' )->willReturn( true );

		$this->searchTableUpdater->method( 'isEnabled' )->willReturn( true );
		$this->searchTableUpdater->method( 'getPropertyTables' )->willReturn( [ $tableDefinition ] );

		return new SearchTableRebuilder( $this->connection, $this->searchTableUpdater );
	}

}
