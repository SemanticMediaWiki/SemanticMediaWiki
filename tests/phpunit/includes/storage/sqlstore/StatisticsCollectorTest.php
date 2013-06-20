<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\StatisticsCollector;
use SMW\StoreFactory;
use SMW\Settings;
use SMW\Store;

/**
 *Test for the StatisticsCollector class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\SQLStore\StatisticsCollector
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 */
class StatisticsCollectorTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\StatisticsCollector';
	}

	/**
	 * Helper method that returns a StatisticsCollector object
	 *
	 * @since 1.9
	 *
	 * @param $count
	 * @param $cacheEnabled
	 *
	 * @return StatisticsCollector
	 */
	private function getInstance( $count = 1, $cacheEnabled = false ) {

		$store = StoreFactory::getStore();

		// fetchObject return object
		$returnFetchObject = new \StdClass;
		$returnFetchObject->count = $count;
		$returnFetchObject->o_hash ='foo';

		// Database stub object which makes the test
		// independent from the real DB
		$connection = $this->getMock( 'DatabaseMysql' );

		// Override methods with expected return objects
		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $returnFetchObject ) ) );

		$connection->expects( $this->any() )
			->method( 'fetchObject' )
			->will( $this->returnValue( $returnFetchObject ) );

		$connection->expects( $this->any() )
			->method( 'estimateRowCount' )
			->will( $this->returnValue( $count ) );

		$connection->expects( $this->any() )
			->method( 'numRows' )
			->will( $this->returnValue( $count ) );

		// Settings to be used
		// hash = HashBagOStuff is used for testing only
		$settings = Settings::newFromArray( array(
			'smwgCacheType' => 'hash',
			'smwgStatisticsCache' => $cacheEnabled,
			'smwgStatisticsCacheExpiry' => 3600
		) );

		return new StatisticsCollector( $store, $connection, $settings );
	}

	/**
	 * @test StatisticsCollector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test StatisticsCollector::newFromStore
	 *
	 * @since 1.9
	 */
	public function testNewFromStore() {
		$instance = StatisticsCollector::newFromStore( StoreFactory::getStore() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test StatisticsCollector::getUsedPropertiesCount
	 * @test StatisticsCollector::getPropertyUsageCount
	 * @test StatisticsCollector::getDeclaredPropertiesCount
	 * @test StatisticsCollector::getSubobjectCount
	 * @test StatisticsCollector::getConceptCount
	 * @test StatisticsCollector::getQueryFormatsCount
	 * @test StatisticsCollector::getQuerySize
	 * @test StatisticsCollector::getQueryCount
	 * @test StatisticsCollector::getPropertyPageCount
	 * @dataProvider getFunctionDataProvider
	 *
	 * @since 1.9
	 */
	public function testFunctions( $function, $expectedType ) {
		$instance = $this->getInstance();
		$result = call_user_func( array( &$instance, $function ) );

		$this->assertInternalType( $expectedType, $result );
	}

	/**
	 * @test StatisticsCollector::getResults
	 * @dataProvider getCollectorDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $segment
	 * @param $expectedType
	 */
	public function testGetResults( $segment, $expectedType ) {
		$instance = $this->getInstance();
		$result = $instance->getResults();

		$this->assertInternalType( $expectedType, $result[$segment] );
	}

	/**
	 * @test StatisticsCollector::getResults
	 * @dataProvider getCacheNonCacheDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $expected
	 */
	public function testCachNoCache( array $test, array $expected ) {

		// Sample A
		$instance = $this->getInstance( $test['A'], $test['cacheEnabled'] );
		$result = $instance->getResults();
		$this->assertEquals( $expected['A'], $result['OWNPAGE'] );

		// Sample B
		$instance = $this->getInstance( $test['B'], $test['cacheEnabled'] );
		$result = $instance->getResults();
		$this->assertEquals( $expected['B'], $result['OWNPAGE'] );

		$this->assertEquals( $test['cacheEnabled'], $instance->isCached() );

	}

	/**
	 * DataProvider
	 *
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
	 * DataProvider
	 *
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
	 * Cache and non-cache data tests sample
	 *
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
