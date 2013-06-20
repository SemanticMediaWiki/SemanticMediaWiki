<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\WantedPropertiesCollector;
use SMW\StoreFactory;
use SMW\DIProperty;
use SMW\Settings;

use SMWRequestOptions;

/**
 * Test for the WantedPropertiesCollector class
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
 * @covers \SMW\SQLStore\WantedPropertiesCollector
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 */
class WantedPropertiesCollectorTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\WantedPropertiesCollector';
	}

	/**
	 * Helper method that returns a WantedPropertiesCollector object
	 *
	 * @since 1.9
	 *
	 * @param $property
	 * @param $count
	 * @param $cacheEnabled
	 *
	 * @return WantedPropertiesCollector
	 */
	private function getInstance( $property = 'Foo', $count = 1, $cacheEnabled = false ) {

		$store = StoreFactory::getStore();

		// Injection object expected as the DB fetchObject
		$returnFetchObject = new \StdClass;
		$returnFetchObject->count = $count;
		$returnFetchObject->smw_title = $property;

		// Database stub object to make the test independent from any real DB
		$connection = $this->getMock( 'DatabaseMysql' );

		// Override method with expected return objects
		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $returnFetchObject ) ) );

		// Settings to be used
		$settings = Settings::newFromArray( array(
			'smwgPDefaultType' => '_wpg',
			'smwgCacheType' => 'hash',
			'smwgWantedPropertiesCache' => $cacheEnabled,
			'smwgWantedPropertiesCacheExpiry' => 360,
		) );

		return new WantedPropertiesCollector( $store, $connection, $settings );
	}

	/**
	 * @test WantedPropertiesCollector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test WantedPropertiesCollector::newFromStore
	 *
	 * @since 1.9
	 */
	public function testNewFromStore() {
		$instance = WantedPropertiesCollector::newFromStore( smwfGetStore() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test WantedPropertiesCollector::getResults
	 * @test WantedPropertiesCollector::count
	 *
	 * @since 1.9
	 */
	public function testGetResults() {

		$count = rand();
		$property = $this->getRandomString();
		$expected = array( array( new DIProperty( $property ), $count ) );

		$instance = $this->getInstance( $property, $count );
		$instance->setRequestOptions(
			new SMWRequestOptions( $property, SMWRequestOptions::STRCOND_PRE )
		);

		$this->assertEquals( $expected, $instance->getResults() );
		$this->assertEquals( 1, $instance->count() );

	}

	/**
	 * @test WantedPropertiesCollector::getResults
	 * @test WantedPropertiesCollector::isCached
	 * @dataProvider getCacheNonCacheDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $expected
	 * @param $info
	 */
	public function testCacheNoCache( array $test, array $expected, array $info ) {

		// Sample A
		$instance = $this->getInstance(
			$test['A']['property'],
			$test['A']['count'],
			$test['cacheEnabled']
		);
		$result = $instance->getResults();
		$this->assertEquals( $expected['A'], $result, $info['msg'] );

		// Sample B
		$instance = $this->getInstance(
			$test['B']['property'],
			$test['B']['count'],
			$test['cacheEnabled']
		);
		$result = $instance->getResults();
		$this->assertEquals( $expected['B'], $result, $info['msg'] );

		$this->assertEquals( $test['cacheEnabled'], $instance->isCached() );
	}

	/**
	 * Cache and non-cache data tests sample
	 *
	 * @return array
	 */
	public function getCacheNonCacheDataProvider() {
		$propertyA = $this->getRandomString();
		$propertyB = $this->getRandomString();
		$countA = rand();
		$countB = rand();

		return array(
			array(

				// #0 Cached
				array(
					'cacheEnabled' => true,
					'A' => array( 'property' => $propertyA, 'count' => $countA ),
					'B' => array( 'property' => $propertyB, 'count' => $countB ),
				),
				array(
					'A' => array( array( new DIProperty( $propertyA ), $countA ) ),
					'B' => array( array( new DIProperty( $propertyA ), $countA ) )
				),
				array( 'msg' => 'Failed asserting that A & B are identical for a cached result' )
			),
			array(

				// #1 Non-cached
				array(
					'cacheEnabled' => false,
					'A' => array( 'property' => $propertyA, 'count' => $countA ),
					'B' => array( 'property' => $propertyB, 'count' => $countB )
				),
				array(
					'A' => array( array( new DIProperty( $propertyA ), $countA ) ),
					'B' => array( array( new DIProperty( $propertyB ), $countB ) )
				),
				array( 'msg' => 'Failed asserting that A & B are not identical for a non-cached result' )
			)
		);
	}
}
