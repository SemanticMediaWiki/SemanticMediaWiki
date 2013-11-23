<?php

namespace SMW\Test\SQLStore;

use FakeResultWrapper;
use SMW\DIProperty;
use SMW\SQLStore\WantedPropertiesCollector;

use SMW\StoreFactory;

use SMWRequestOptions;

/**
 * @covers \SMW\SQLStore\WantedPropertiesCollector
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class WantedPropertiesCollectorTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\WantedPropertiesCollector';
	}

	/**
	 * @since 1.9
	 *
	 * @return WantedPropertiesCollector
	 */
	private function newInstance( $store = null, $property = 'Foo', $count = 1, $cacheEnabled = false ) {

		if ( $store === null ) {
			$store = $this->newMockBuilder()->newObject( 'Store' );
		}

		$result = array(
			'count'     => $count,
			'smw_title' => $property
		);

		$connection = $this->newMockBuilder()->newObject( 'DatabaseBase', array(
			'select' => new FakeResultWrapper( array( (object)$result ) )
		) );

		$settings = $this->newSettings( array(
			'smwgPDefaultType'                => '_wpg',
			'smwgCacheType'                   => 'hash',
			'smwgWantedPropertiesCache'       => $cacheEnabled,
			'smwgWantedPropertiesCacheExpiry' => 360,
		) );

		return new WantedPropertiesCollector( $store, $connection, $settings );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testGetResultsOnSQLStore() {

		$store = StoreFactory::getStore( 'SMWSQLStore3' );

		$count = rand();
		$property = $this->newRandomString();
		$expected = array( array( new DIProperty( $property ), $count ) );

		$instance = $this->newInstance( $store, $property, $count );
		$instance->setRequestOptions(
			new SMWRequestOptions( $property, SMWRequestOptions::STRCOND_PRE )
		);

		$this->assertEquals( $expected, $instance->getResults() );
		$this->assertEquals( 1, $instance->getCount() );

	}

	/**
	 * @since 1.9
	 */
	public function testIsFixedPropertyTableOnSQLMockStore() {

		$tableDefinition = $this->newMockBuilder()->newObject( 'SQLStoreTableDefinition', array(
			'isFixedPropertyTable' => true
		) );

		$store = $this->newMockBuilder()->newObject( 'Store', array(
			'getPropertyTables' => array( 'Foo' => $tableDefinition ),
			'findTypeTableId'   => 'Foo'
		) );

		$result = $this->newInstance( $store )->runCollector();

		$this->assertInternalType(
			'array',
			$result,
			'Asserts that runCollector() returns an array'
		);

		$this->assertEmpty(
			$result,
			'Asserts that runCollector() returns an empty array'
		);

	}

	/**
	 * @dataProvider getCacheNonCacheDataProvider
	 *
	 * @since 1.9
	 */
	public function testCacheNoCacheOnSQLStore( array $test, array $expected, array $info ) {

		$store = StoreFactory::getStore( 'SMWSQLStore3' );

		// Sample A
		$instance = $this->newInstance(
			$store,
			$test['A']['property'],
			$test['A']['count'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['A'], $instance->getResults(), $info['msg'] );

		// Sample B
		$instance = $this->newInstance(
			$store,
			$test['B']['property'],
			$test['B']['count'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['B'], $instance->getResults(), $info['msg'] );
		$this->assertEquals( $test['cacheEnabled'], $instance->isCached() );
	}

	/**
	 * @return array
	 */
	public function getCacheNonCacheDataProvider() {
		$propertyA = $this->newRandomString();
		$propertyB = $this->newRandomString();
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
