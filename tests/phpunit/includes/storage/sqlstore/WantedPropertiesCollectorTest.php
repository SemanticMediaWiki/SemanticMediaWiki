<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\WantedPropertiesCollector;
use SMW\StoreFactory;
use SMW\DIProperty;
use SMW\Settings;

use SMWRequestOptions;

use FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\WantedPropertiesCollector
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
class WantedPropertiesCollectorTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\WantedPropertiesCollector';
	}

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

	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	public function testGetResultsOnSQLStore() {

		$store = StoreFactory::getStore( 'SMWSQLStore3' );

		$count = rand();
		$property = $this->newRandomString();
		$expected = array( array( new DIProperty( $property ), $count ) );

		$instance = $this->newInstance( $store, $property, $count );
		$instance->setRequestOptions(
			new SMWRequestOptions( $property, \SMWStringCondition::STRCOND_PRE )
		);

		$this->assertEquals( $expected, $instance->getResults() );
		$this->assertEquals( 1, $instance->getCount() );

	}

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

	public function testUnknownPredefinedPropertyThrowsExceptionToReturnErrorDataItem() {

		$tableDefinition = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'isFixedPropertyTable', 'getName' ) )
			->getMock();

		$tableDefinition->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( false ) );

		$tableDefinition->expects( $this->once() )
			->method( 'getName' )
			->will( $this->returnValue( 'Bar' ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->setMethods( array( 'getPropertyTables', 'findTypeTableId' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $tableDefinition ) ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'findTypeTableId' )
			->will( $this->returnValue( 'Foo' ) );

		$row = new \stdClass();
		$row->smw_title = '_UnknownPredefinedProperty';
		$row->count = 0;

		$dbConnection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'select' ) )
			->getMockForAbstractClass();

		$dbConnection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array( $row ) ) );

		$settings = Settings::newFromArray( array(
			'smwgPDefaultType' => 'Foo'
		) );

		$instance = new WantedPropertiesCollector( $store, $dbConnection, $settings );
		$results = $instance->runCollector();

		$this->assertInternalType( 'array', $results );
		$this->assertInstanceOf( 'SMWDIError', $results[0][0] );
	}

}
