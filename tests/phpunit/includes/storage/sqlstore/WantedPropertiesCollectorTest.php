<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\WantedPropertiesCollector;
use SMW\StoreFactory;
use SMW\DIProperty;
use SMW\Settings;

use SMWRequestOptions;

use FakeResultWrapper;

/**
 * Test for the WantedPropertiesCollector class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
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
	 * Helper method that returns a Database object
	 *
	 * @since 1.9
	 *
	 * @param $smwTitle
	 * @param $count
	 *
	 * @return Database
	 */
	private function getMockDBConnection( $smwTitle = 'Foo', $count = 1 ) {

		// Injection object expected as the DB fetchObject
		$result = array(
			'count'     => $count,
			'smw_title' => $smwTitle
		);

		// Database stub object to make the test independent from any real DB
		$connection = $this->getMock( 'DatabaseMysql' );

		// Override method with expected return objects
		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( new FakeResultWrapper( array( (object)$result ) ) ) );

		return $connection;
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
		$connection = $this->getMockDBConnection( $property, $count );

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
	 * @test WantedPropertiesCollector::getCount
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
		$this->assertEquals( 1, $instance->getCount() );

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

		$this->assertEquals( $expected['A'], $instance->getResults(), $info['msg'] );

		// Sample B
		$instance = $this->getInstance(
			$test['B']['property'],
			$test['B']['count'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['B'], $instance->getResults(), $info['msg'] );
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
