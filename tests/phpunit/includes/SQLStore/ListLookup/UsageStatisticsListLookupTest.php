<?php

namespace SMW\Tests\SQLStore\ListLookup;

use SMW\SQLStore\ListLookup\UsageStatisticsListLookup;

/**
 * @covers \SMW\SQLStore\ListLookup\UsageStatisticsListLookup
 *
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UsageStatisticsListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ListLookup\UsageStatisticsListLookup',
			new UsageStatisticsListLookup( $store, $propertyStatisticsStore )
		);
	}

	public function testListLookupInterfaceMethodAccess() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UsageStatisticsListLookup( $store, $propertyStatisticsStore );

		$this->assertInternalType(
			'string',
			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isCached()
		);

		$this->assertEquals(
			'statistics-lookup',
			$instance->getLookupIdentifier()
		);
	}

	public function testfetchListForInvalidTableThrowsException() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Bar' ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => 'throwExceptionForMismatch' ) ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new UsageStatisticsListLookup( $store, $propertyStatisticsStore );

		$this->setExpectedException( 'RuntimeException' );
		$instance->fetchList();
	}

	/**
	 * @dataProvider bySegmentDataProvider
	 */
	public function testfetchList( $segment, $type ) {

		$row = new \stdClass;
		$row->o_hash = 42;
		$row->count = 1001;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdFetcher = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( array( 'getSMWPropertyID' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdFetcher ) );

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();

		$propertyStatisticsStore->expects( $this->any() )
			->method( 'getUsageCount' )
			->will( $this->returnValue( 54 ) );

		$instance = new UsageStatisticsListLookup( $store, $propertyStatisticsStore );
		$result = $instance->fetchList();

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertArrayHasKey(
			$segment,
			$result
		);

		$this->assertInternalType(
			$type,
			$result[$segment]
		);
	}

	public function bySegmentDataProvider() {
		return array(
			array( 'OWNPAGE',      'integer' ),
			array( 'QUERY',        'integer' ),
			array( 'QUERYSIZE',    'integer' ),
			array( 'QUERYFORMATS', 'array'   ),
			array( 'CONCEPTS',     'integer' ),
			array( 'SUBOBJECTS',   'integer' ),
			array( 'DECLPROPS',    'integer' ),
			array( 'USEDPROPS',    'integer' ),
			array( 'PROPUSES',     'integer' ),
			array( 'ERRORUSES',    'integer' )
		);
	}

}
