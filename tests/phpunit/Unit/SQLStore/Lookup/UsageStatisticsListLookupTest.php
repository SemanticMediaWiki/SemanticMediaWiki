<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( '\SMW\SQLStore\PropertyStatisticsStore' )
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
			$instance->isFromCache()
		);

		$this->assertEquals(
			'statistics-lookup',
			$instance->getHash()
		);
	}

	public function testfetchListForInvalidTableThrowsException() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( new \FakeResultWrapper( [] ) ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Bar' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => 'throwExceptionForMismatch' ] ) );

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
			->will( $this->returnValue( new \FakeResultWrapper( [ $row ] ) ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdFetcher = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdFetcher ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => $tableDefinition ] ) );

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
		return [
			[ 'OWNPAGE',      'integer' ],
			[ 'QUERY',        'integer' ],
			[ 'QUERYSIZE',    'integer' ],
			[ 'QUERYFORMATS', 'array'   ],
			[ 'CONCEPTS',     'integer' ],
			[ 'SUBOBJECTS',   'integer' ],
			[ 'DECLPROPS',    'integer' ],
			[ 'USEDPROPS',    'integer' ],
			[ 'TOTALPROPS',   'integer' ],
			[ 'PROPUSES',     'integer' ],
			[ 'ERRORUSES',    'integer' ],
			[ 'DELETECOUNT',  'integer' ]
		];
	}

}
