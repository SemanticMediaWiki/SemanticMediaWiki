<?php

namespace SMW\Tests\Maintenance;

use FakeResultWrapper;
use SMW\Maintenance\PropertyStatisticsRebuilder;

/**
 * @covers \SMW\Maintenance\PropertyStatisticsRebuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class PropertyStatisticsRebuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			PropertyStatisticsRebuilder::class,
			new PropertyStatisticsRebuilder( $store, $propertyStatisticsStore )
		);
	}

	public function testRebuildStatisticsStoreAndInsertCountRows() {

		$tableName = 'Foobar';

		$uRow = new \stdClass;
		$uRow->count = 1111;

		$nRow = new \stdClass;
		$nRow->count = 1;

		$res = [
			'smw_title' => 'Foo',
			'smw_id' => 9999
		];

		$resultWrapper = new FakeResultWrapper(
			[ (object)$res ]
		);

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getTableFields' )
			->will( $this->returnValue( [] ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$database->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->with(
				$this->stringContains( $tableName ),
				$this->anything(),
				$this->equalTo( [ 'p_id' => 9999 ] ),
				$this->anything() )
			->will( $this->onConsecutiveCalls( $uRow, $nRow ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ $this->newPropertyTable( $tableName ) ] ) );

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore->expects( $this->atLeastOnce() )
			->method( 'insertUsageCount' )
			->with(
				$this->equalTo( 9999 ),
				$this->equalTo( [ 1110, 1 ] ) );

		$instance = new PropertyStatisticsRebuilder(
			$store,
			$propertyStatisticsStore
		);

		$instance->rebuild();
	}

	protected function newPropertyTable( $propertyTableName, $fixedPropertyTable = false ) {

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->setMethods( [ 'isFixedPropertyTable', 'getName' ] )
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( $fixedPropertyTable ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( $propertyTableName ) );

		return $propertyTable;
	}

}
