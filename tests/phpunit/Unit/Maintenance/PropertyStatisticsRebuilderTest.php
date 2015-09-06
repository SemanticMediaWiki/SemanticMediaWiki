<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\PropertyStatisticsRebuilder;
use FakeResultWrapper;

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

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\Maintenance\PropertyStatisticsRebuilder',
			new PropertyStatisticsRebuilder( $store, $propertyStatisticsStore )
		);
	}

	public function testRebuildWithValidPropertyStatisticsStoreInsertUsageCount() {

		$arbitraryPropertyTableName = 'allornothing';

		$propertySelectRow = new \stdClass;
		$propertySelectRow->count = 1111;

		$selectResult = array(
			'smw_title'   => 'Foo',
			'smw_id'      => 9999
		);

		$selectResultWrapper = new FakeResultWrapper( array( (object)$selectResult ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( $selectResultWrapper ) );

		$database->expects( $this->once() )
			->method( 'selectRow' )
			->with( $this->stringContains( $arbitraryPropertyTableName ),
				$this->anything(),
				$this->equalTo( array( 'p_id' => 9999 ) ),
				$this->anything() )
			->will( $this->returnValue( $propertySelectRow ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array(
				$this->getNonFixedPropertyTable( $arbitraryPropertyTableName ) )
			) );

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore->expects( $this->atLeastOnce() )
			->method( 'insertUsageCount' );

		$instance = new PropertyStatisticsRebuilder(
			$store,
			$propertyStatisticsStore
		);

		$instance->rebuild();
	}

	protected function getNonFixedPropertyTable( $propertyTableName ) {

		$propertyTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array(
				'isFixedPropertyTable',
				'getName' ) )
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( $propertyTableName ) );

		return $propertyTable;
	}

}
