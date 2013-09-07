<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\PropertiesCollector;

use SMW\Test\SemanticMediaWikiTestCase;

use SMW\MessageFormatter;
use SMW\StoreFactory;
use SMW\DIProperty;

use SMWStringCondition;
use SMWRequestOptions;

use FakeResultWrapper;

/**
 * Test for the PropertiesCollector class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SQLStore\PropertiesCollector
 * @covers \SMW\InvalidPropertyException
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 */
class PropertiesCollectorTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\PropertiesCollector';
	}

	/**
	 * Helper method that returns a Database object
	 *
	 * @since 1.9
	 *
	 * @param $smwTitle
	 * @param $usageCount
	 *
	 * @return Database
	 */
	private function getMockDBConnection( $smwTitle = 'Foo', $usageCount = 1 ) {

		// Id is randomized, not publicly exposed and only internally
		// shared between PropertyStatisticsTable::getUsageCounts and
		// $this->store->getObjectIds()->getIdTable()
		$smwId = rand();

		$result = array(
			'smw_title'   => $smwTitle,
			'smw_id'      => $smwId,
			'p_id'        => (string)$smwId, // PropertyStatisticsTable assert ctype_digit
			'usage_count' => (string)$usageCount // PropertyStatisticsTable assert ctype_digit
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
	 * Helper method that returns a PropertiesCollector object
	 *
	 * @since 1.9
	 *
	 * @param $smwTitle
	 * @param $cacheEnabled
	 *
	 * @return PropertiesCollector
	 */
	private function newInstance( $smwTitle = 'Foo', $usageCount = 1, $cacheEnabled = false ) {

		$mockStore  = $this->newMockBuilder()->newObject( 'Store' );
		$connection = $this->getMockDBConnection( $smwTitle, $usageCount );

		$settings = $this->newSettings( array(
			'smwgCacheType'             => 'hash',
			'smwgPropertiesCache'       => $cacheEnabled,
			'smwgPropertiesCacheExpiry' => 360,
		) );

		return new PropertiesCollector( $mockStore, $connection, $settings );
	}

	/**
	 * @test PropertiesCollector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test PropertiesCollector::newFromStore
	 *
	 * @since 1.9
	 */
	public function testNewFromStore() {
		$instance = PropertiesCollector::newFromStore( StoreFactory::getStore() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test PropertiesCollector::getResults
	 * @test PropertiesCollector::getCount
	 *
	 * @since 1.9
	 */
	public function testGetResults() {

		$property = $this->getRandomString();
		$count    = rand();
		$expected = array( new DIProperty( $property ), $count );

		$instance = $this->newInstance( $property, $count );
		$requestOptions = new SMWRequestOptions();
		$requestOptions->limit = 1;
		$requestOptions->addStringCondition( $property, SMWStringCondition::STRCOND_MID  );

		$instance->setRequestOptions( $requestOptions );

		$this->assertEquals( array( $expected ), $instance->getResults() );
		$this->assertEquals( 1, $instance->getCount() );

	}

	/**
	 * @test PropertiesCollector::getResults
	 * @dataProvider exceptionDataProvider
	 *
	 * InvalidPropertyException is thrown but caught and is retuned as a
	 * SMWDIError object instead
	 *
	 * @since 1.9
	 *
	 * @param $property
	 */
	public function testInvalidPropertyException( $property ) {

		$instance = $this->newInstance( $property );
		$results  = $instance->getResults();

		$this->assertInternalType( 'array', $results );
		$this->assertEquals( 1, $instance->getCount() );
		$this->assertInstanceOf( 'SMWDIError', $results[0][0] );
		$this->assertContains(
			$property,
			MessageFormatter::newFromArray( $this->getLanguage(), array( $results[0][0]->getErrors() ) )->getHtml()
		);

	}

	/**
	 * @test PropertiesCollector::getResults
	 * @test PropertiesCollector::isCached
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
		$instance = $this->newInstance(
			$test['A']['property'],
			$test['A']['count'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['A'], $instance->getResults(), $info['msg'] );

		// Sample B
		$instance = $this->newInstance(
			$test['B']['property'],
			$test['B']['count'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['B'], $instance->getResults(), $info['msg'] );
		$this->assertEquals( $test['cacheEnabled'], $instance->isCached() );
	}

	/**
	 * Exception data sample
	 *
	 * @return array
	 */
	public function exceptionDataProvider() {
		return array( array( '-Lala' ), array( '_Lila' ) );
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
