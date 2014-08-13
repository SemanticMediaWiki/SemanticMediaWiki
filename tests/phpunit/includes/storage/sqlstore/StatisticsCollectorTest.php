<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\StatisticsCollector;
use SMW\StoreFactory;
use SMW\Settings;
use SMW\Store;

use FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\StatisticsCollector
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StatisticsCollectorTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\StatisticsCollector';
	}

	/**
	 * @since 1.9
	 *
	 * @return StatisticsCollector
	 */
	private function newInstance( $count = 55, $cacheEnabled = false, $hash = 'foo' ) {

		$tableDefinition = $this->newMockBuilder()->newObject( 'SQLStoreTableDefinition', array(
			'isFixedPropertyTable' => true
		) );

		$store = $this->newMockBuilder()->newObject( 'Store', array(
			'getPropertyTables'   => array( 'Foo' => $tableDefinition ),
			'findTypeTableId'     => 'Foo',
			'findPropertyTableID' => 'Foo'
		) );

		$result = array(
			'count'  => $count,
			'o_hash' => $hash
		);

		$resultWrapper = new FakeResultWrapper( array( (object)$result ) );
		$resultWrapper->count = $count;

		$connection = $this->newMockBuilder()->newObject( 'DatabaseBase', array(
			'select'      => $resultWrapper,
			'selectRow'   => $resultWrapper,
			'fetchObject' => $resultWrapper,
			'estimateRowCount' => $count
		) );

		$settings = $this->newSettings( array(
			'smwgCacheType' => 'hash',
			'smwgStatisticsCache' => $cacheEnabled,
			'smwgStatisticsCacheExpiry' => 3600
		) );

		return new StatisticsCollector( $store, $connection, $settings );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider getFunctionDataProvider
	 *
	 * @since 1.9
	 */
	public function testFunctions( $function, $expectedType ) {

		$count = rand();
		$hash  = 'Quxxey';
		$expectedCount = $expectedType === 'array' ? array( $hash => $count ) : $count;

		$instance = $this->newInstance( $count, false, $hash );

		$result = call_user_func( array( &$instance, $function ) );

		$this->assertInternalType( $expectedType, $result );
		$this->assertEquals( $expectedCount, $result );
	}

	/**
	 * @dataProvider getCollectorDataProvider
	 *
	 * @since 1.9
	 */
	public function testResultsOnStore( $segment, $expectedType ) {

		$instance = $this->newInstance();
		$result   = $instance->getResults();

		$this->assertInternalType( $expectedType, $result[$segment] );
	}

	/**
	 * @dataProvider getCacheNonCacheDataProvider
	 *
	 * @since 1.9
	 */
	public function testCachNoCache( array $test, array $expected ) {

		// Sample A
		$instance = $this->newInstance( $test['A'], $test['cacheEnabled'] );
		$result = $instance->getResults();

		$this->assertEquals( $expected['A'], $result['OWNPAGE'] );

		// Sample B
		$instance = $this->newInstance( $test['B'], $test['cacheEnabled'] );
		$result = $instance->getResults();
		$this->assertEquals( $expected['B'], $result['OWNPAGE'] );

		$this->assertEquals( $test['cacheEnabled'], $instance->isCached() );

	}

	/**
	 * @return array
	 */
	public function getFunctionDataProvider() {
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

	/**
	 * @return array
	 */
	public function getCollectorDataProvider() {
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

	/**
	 * @return array
	 */
	public function getCacheNonCacheDataProvider() {
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
}
