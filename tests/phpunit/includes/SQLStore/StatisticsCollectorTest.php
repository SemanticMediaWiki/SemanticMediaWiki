<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\StatisticsCollector;
use SMW\Settings;
use SMW\Store;

use FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\StatisticsCollector
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StatisticsCollectorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$settings = $this->getMockBuilder( '\SMW\Settings' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\StatisticsCollector',
			new StatisticsCollector( $store, $settings )
		);
	}

	/**
	 * @dataProvider byFunctionDataProvider
	 */
	public function testFunctions( $function, $expectedType ) {

		$count = rand();

		$hash  = 'Bar';
		$expectedCount = $expectedType === 'array' ? array( $hash => $count ) : $count;

		$store = $this->createMockStoreInstanceFor( $count, $hash );

		$settings = Settings::newFromArray( array(
			'smwgCacheType' => 'hash',
			'smwgStatisticsCache' => false,
			'smwgStatisticsCacheExpiry' => 3600
		) );

		$instance = new StatisticsCollector( $store, $settings );

		$result = call_user_func( array( &$instance, $function ) );

		$this->assertInternalType(
			$expectedType,
			$result
		);

		$this->assertEquals(
			$expectedCount,
			$result
		);
	}

	/**
	 * @dataProvider bySegmentDataProvider
	 */
	public function testResultsBySegment( $segment, $expectedType ) {

		$store = $this->createMockStoreInstanceFor( 42, 'bar' );

		$settings = Settings::newFromArray( array(
			'smwgCacheType' => 'hash',
			'smwgStatisticsCache' => false,
			'smwgStatisticsCacheExpiry' => 3600
		) );

		$instance = new StatisticsCollector( $store, $settings );
		$result = $instance->getResults();

		$this->assertInternalType(
			$expectedType,
			$result[$segment]
		);
	}

	/**
	 * @dataProvider cacheNonCacheDataProvider
	 */
	public function testCachNoCache( array $test, array $expected ) {

		// Sample A
		$store = $this->createMockStoreInstanceFor( $test['A'], 'bar' );

		$settings = Settings::newFromArray( array(
			'smwgCacheType' => 'hash',
			'smwgStatisticsCache' => $test['cacheEnabled'],
			'smwgStatisticsCacheExpiry' => 3600
		) );

		$instance = new StatisticsCollector( $store, $settings );
		$result = $instance->getResults();

		$this->assertEquals(
			$expected['A'],
			$result['OWNPAGE']
		);

		// Sample B
		$store = $this->createMockStoreInstanceFor( $test['B'], 'bar' );

		$settings = Settings::newFromArray( array(
			'smwgCacheType' => 'hash',
			'smwgStatisticsCache' => $test['cacheEnabled'],
			'smwgStatisticsCacheExpiry' => 3600
		) );

		$instance = new StatisticsCollector( $store, $settings );
		$result = $instance->getResults();

		$this->assertEquals(
			$expected['B'],
			$result['OWNPAGE']
		);

		$this->assertEquals(
			$test['cacheEnabled'],
			$instance->isCached()
		);
	}

	public function byFunctionDataProvider() {
		return array(
			array( 'getUsedPropertiesCount',     'integer' ),
			array( 'getPropertyUsageCount',      'integer' ),
			array( 'getDeclaredPropertiesCount', 'integer' ),
			array( 'getSubobjectCount',          'integer' ),
			array( 'getConceptCount',            'integer' ),
			array( 'getQueryFormatsCount',       'array'   ),
			array( 'getQuerySize',               'integer' ),
			array( 'getQueryCount',              'integer' ),
			array( 'getPropertyPageCount',       'integer' )
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
			array( 'PROPUSES',     'integer' )
		);
	}

	public function cacheNonCacheDataProvider() {
		return array(
			array(

				// #0 Invoke different A & B count but expect that
				// A value is returned for both since cache is enabled
				array( 'cacheEnabled' => true,  'A' => 1001, 'B' => 9001 ),
				array( 'A' => 1001, 'B' => 1001 )
			),
			array(

				// #1 Invoke different A & B count and expect that since
				// cache is disabled the original result is returned
				array( 'cacheEnabled' => false, 'A' => 2001, 'B' => 9001 ),
				array( 'A' => 2001, 'B' => 9001 )
			)
		);
	}

	private function createMockStoreInstanceFor( $count = 55, $hash = 'foo' ) {

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$result = array(
			'count'  => $count,
			'o_hash' => $hash
		);

		$resultWrapper = new FakeResultWrapper( array( (object)$result ) );
		$resultWrapper->count = $count;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( $resultWrapper ) );

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( $resultWrapper ) );

		$connection->expects( $this->any() )
			->method( 'fetchObject' )
			->will( $this->returnValue( $resultWrapper ) );

		$connection->expects( $this->any() )
			->method( 'estimateRowCount' )
			->will( $this->returnValue( $count ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->any() )
			->method( 'findTypeTableId' )
			->will( $this->returnValue( 'Foo' ) );

		return $store;
	}

}
