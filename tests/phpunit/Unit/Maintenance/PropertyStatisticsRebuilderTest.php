<?php

namespace SMW\Tests\Unit\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\PropertyStatisticsRebuilder;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use stdClass;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\Maintenance\PropertyStatisticsRebuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class PropertyStatisticsRebuilderTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			PropertyStatisticsRebuilder::class,
			new PropertyStatisticsRebuilder( $store, $propertyStatisticsStore )
		);
	}

	public function testRebuildStatisticsStoreAndInsertCountRows() {
		$tableName = 'Foobar';

		$uRow = new stdClass;
		$uRow->count = 1111;

		$nRow = new stdClass;
		$nRow->count = 1;

		$res = [
			'smw_title' => 'Foo',
			'smw_id' => 9999
		];

		$resultWrapper = new FakeResultWrapper(
			[ (object)$res ]
		);

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getTableFields' )
			->willReturn( [] );

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( $resultWrapper );

		$database->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->with(
				$this->stringContains( $tableName ),
				$this->anything(),
				[ 'p_id' => 9999 ],
				$this->anything() )
			->willReturnOnConsecutiveCalls( $uRow, $nRow );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $database );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ $this->newPropertyTable( $tableName ) ] );

		$propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore->expects( $this->atLeastOnce() )
			->method( 'insertUsageCount' )
			->with(
				9999,
				$this->equalTo( [ 1110, 1 ] ) );

		$instance = new PropertyStatisticsRebuilder(
			$store,
			$propertyStatisticsStore
		);

		$instance->rebuild();
	}

	protected function newPropertyTable( $propertyTableName, $fixedPropertyTable = false ) {
		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isFixedPropertyTable', 'getName' ] )
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( $fixedPropertyTable );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( $propertyTableName );

		return $propertyTable;
	}

}
