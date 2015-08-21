<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\UsageStatisticsListLookup;

/**
 * @covers \SMW\SQLStore\Lookup\UsageStatisticsListLookup
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class UsageStatisticsListLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( '\SMW\Store\PropertyStatisticsStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\UsageStatisticsListLookup',
			new UsageStatisticsListLookup( $this->store, $this->propertyStatisticsStore )
		);
	}

	public function testListLookupInterfaceMethodAccess() {

		$instance = new UsageStatisticsListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

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

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Bar' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => 'throwExceptionForMismatch' ) ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new UsageStatisticsListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

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

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdFetcher ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->propertyStatisticsStore->expects( $this->any() )
			->method( 'getUsageCount' )
			->will( $this->returnValue( 54 ) );

		$instance = new UsageStatisticsListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

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
