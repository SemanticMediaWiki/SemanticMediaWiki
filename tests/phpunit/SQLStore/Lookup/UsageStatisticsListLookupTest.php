<?php

namespace SMW\Tests\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use stdClass;
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
class UsageStatisticsListLookupTest extends TestCase {

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UsageStatisticsListLookup::class,
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
		$connection = $this->getMockBuilder( Database::class )
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
		$row = new stdClass;
		$row->o_hash = 42;
		$row->count = 1001;

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( new FakeResultWrapper( [ $row ] ) );

		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
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
