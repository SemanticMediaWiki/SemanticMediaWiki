<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\Lookup\UsageStatisticsListLookup
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since   2.2
 *
 * @author mwjames
 */
class UsageStatisticsListLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp(): void {
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

		$this->assertIsString(

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
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( new FakeResultWrapper( [] ) );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Bar' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => 'throwExceptionForMismatch' ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new UsageStatisticsListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

		$this->expectException( 'RuntimeException' );
		$instance->fetchList();
	}

	/**
	 * @dataProvider integerSegmentProvider
	 */
	public function testfetchListReturnsIntegerForSegment( $segment ) {
		$result = $this->fetchListResult();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( $segment, $result );
		$this->assertIsInt( $result[$segment] );
	}

	public function testfetchListReturnsArrayForQueryFormats() {
		$result = $this->fetchListResult();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'QUERYFORMATS', $result );
		$this->assertIsArray( $result['QUERYFORMATS'] );
	}

	public function integerSegmentProvider() {
		return [
			[ 'OWNPAGE' ],
			[ 'QUERY' ],
			[ 'QUERYSIZE' ],
			[ 'CONCEPTS' ],
			[ 'SUBOBJECTS' ],
			[ 'DECLPROPS' ],
			[ 'USEDPROPS' ],
			[ 'TOTALPROPS' ],
			[ 'PROPUSES' ],
			[ 'ERRORUSES' ],
			[ 'DELETECOUNT' ],
		];
	}

	private function fetchListResult(): array {
		$row = new \stdClass;
		$row->o_hash = 42;
		$row->count = 1001;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdFetcher = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $objectIdFetcher );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->propertyStatisticsStore->expects( $this->any() )
			->method( 'getUsageCount' )
			->willReturn( 54 );

		$instance = new UsageStatisticsListLookup(
			$this->store,
			$this->propertyStatisticsStore
		);

		return $instance->fetchList();
	}

}
