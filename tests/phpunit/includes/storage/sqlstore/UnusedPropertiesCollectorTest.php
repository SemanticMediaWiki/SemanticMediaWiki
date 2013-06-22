<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\UnusedPropertiesCollector;
use SMW\MessageFormatter;
use SMW\StoreFactory;
use SMW\DIProperty;
use SMW\Settings;

use SMWRequestOptions;

/**
 * Test for the UnusedPropertiesCollector class
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
 * @covers \SMW\SQLStore\UnusedPropertiesCollector
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 */
class UnusedPropertiesCollectorTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\UnusedPropertiesCollector';
	}

	/**
	 * Helper method that returns a Database object
	 *
	 * @since 1.9
	 *
	 * @param $smwTitle
	 *
	 * @return Database
	 */
	private function getMockDBConnection( $smwTitle = 'Foo' ) {

		// Injection object expected as the DB fetchObject
		$returnFetchObject = new \StdClass;
		$returnFetchObject->smw_title = $smwTitle;

		// Database stub object to make the test independent from any real DB
		$connection = $this->getMock( 'DatabaseMysql' );

		// Override method with expected return objects
		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array( $returnFetchObject ) ) );

		return $connection;
	}

	/**
	 * Helper method that returns a UnusedPropertiesCollector object
	 *
	 * @since 1.9
	 *
	 * @param $smwTitle
	 * @param $cacheEnabled
	 *
	 * @return UnusedPropertiesCollector
	 */
	private function getInstance( $smwTitle = 'Foo', $cacheEnabled = false ) {

		$store = StoreFactory::getStore();
		$connection = $this->getMockDBConnection( $smwTitle );

		// Settings to be used
		$settings = Settings::newFromArray( array(
			'smwgCacheType' => 'hash',
			'smwgUnusedPropertiesCache' => $cacheEnabled,
			'smwgUnusedPropertiesCacheExpiry' => 360,
		) );

		return new UnusedPropertiesCollector( $store, $connection, $settings );
	}

	/**
	 * @test UnusedPropertiesCollector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test UnusedPropertiesCollector::newFromStore
	 *
	 * @since 1.9
	 */
	public function testNewFromStore() {
		$instance = UnusedPropertiesCollector::newFromStore( StoreFactory::getStore() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test UnusedPropertiesCollector::getResults
	 * @test UnusedPropertiesCollector::count
	 *
	 * @since 1.9
	 */
	public function testGetResults() {

		$property = $this->getRandomString();
		$expected = array( new DIProperty( $property ) );

		$instance = $this->getInstance( $property );
		$requestOptions = new SMWRequestOptions( $property, SMWRequestOptions::STRCOND_PRE );
		$requestOptions->limit = 1;

		$instance->setRequestOptions( $requestOptions );

		$this->assertEquals( $expected, $instance->getResults() );
		$this->assertEquals( 1, $instance->count() );

	}

	/**
	 * @test UnusedPropertiesCollector::getResults
	 *
	 * InvalidPropertyException is thrown but caught and returning with a
	 * SMWDIError instead
	 *
	 * @since 1.9
	 */
	public function testInvalidPropertyException() {

		// -<property> is to raise an error
		$property = '-Lala';
		$instance = $this->getInstance( $property );

		$results = $instance->getResults();
		$message = MessageFormatter::newFromArray( $this->getLanguage(), array( $results[0]->getErrors() ) )->getHtml();

		$this->assertInternalType( 'array', $results );
		$this->assertEquals( 1, $instance->count() );
		$this->assertInstanceOf( 'SMWDIError', $results[0] );
		$this->assertContains( $property, $message );

	}

	/**
	 * @test UnusedPropertiesCollector::getResults
	 * @test UnusedPropertiesCollector::isCached
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
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['A'], $instance->getResults(), $info['msg'] );

		// Sample B
		$instance = $this->getInstance(
			$test['B']['property'],
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

		return array(
			array(

				// #0 Cached
				array(
					'cacheEnabled' => true,
					'A' => array( 'property' => $propertyA ),
					'B' => array( 'property' => $propertyB ),
				),
				array(
					'A' => array( new DIProperty( $propertyA ) ),
					'B' => array( new DIProperty( $propertyA ) )
				),
				array( 'msg' => 'Failed asserting that A & B are identical for a cached result' )
			),
			array(

				// #1 Non-cached
				array(
					'cacheEnabled' => false,
					'A' => array( 'property' => $propertyA ),
					'B' => array( 'property' => $propertyB )
				),
				array(
					'A' => array( new DIProperty( $propertyA ) ),
					'B' => array( new DIProperty( $propertyB ) )
				),
				array( 'msg' => 'Failed asserting that A & B are not identical for a non-cached result' )
			)
		);
	}
}
