<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\UnusedPropertiesCollector;

use SMW\MessageFormatter;
use SMW\StoreFactory;
use SMW\DIProperty;

use SMWRequestOptions;

use FakeResultWrapper;

/**
 * @covers \SMW\SQLStore\UnusedPropertiesCollector
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
class UnusedPropertiesCollectorTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\UnusedPropertiesCollector';
	}

	/**
	 * @since 1.9
	 *
	 * @return UnusedPropertiesCollector
	 */
	private function newInstance( $smwTitle = 'Foo', $cacheEnabled = false ) {

		$mockStore  = $this->newMockBuilder()->newObject( 'Store' );

		$result = array(
			'smw_title' => $smwTitle,
		);

		$connection = $this->newMockBuilder()->newObject( 'DatabaseBase', array(
			'select' => new FakeResultWrapper( array( (object)$result ) )
		) );

		$settings = $this->newSettings( array(
			'smwgCacheType'                   => 'hash',
			'smwgUnusedPropertiesCache'       => $cacheEnabled,
			'smwgUnusedPropertiesCacheExpiry' => 360,
		) );

		return new UnusedPropertiesCollector( $mockStore, $connection, $settings );
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
	public function testGetResults() {

		$property = $this->newRandomString();
		$expected = array( new DIProperty( $property ) );

		$instance = $this->newInstance( $property );
		$requestOptions = new SMWRequestOptions( $property, \SMWStringCondition::STRCOND_PRE );
		$requestOptions->limit = 1;

		$instance->setRequestOptions( $requestOptions );

		$this->assertEquals( $expected, $instance->getResults() );
		$this->assertEquals( 1, $instance->getCount() );

	}

	/**
	 * @dataProvider exceptionDataProvider
	 *
	 * InvalidPropertyException is thrown but caught and returning with a
	 * SMWDIError instead
	 *
	 * @since 1.9
	 */
	public function testInvalidPropertyException( $property ) {

		$instance = $this->newInstance( $property );
		$results  = $instance->getResults();

		$this->assertInternalType( 'array', $results );
		$this->assertEquals( 1, $instance->getCount() );
		$this->assertInstanceOf( 'SMWDIError', $results[0] );
		$this->assertContains(
			$property,
			MessageFormatter::newFromArray( $this->getLanguage(), array( $results[0]->getErrors() ) )->getHtml()
		);

	}

	/**
	 * @dataProvider getCacheNonCacheDataProvider
	 *
	 * @since 1.9
	 */
	public function testCacheNoCache( array $test, array $expected, array $info ) {

		// Sample A
		$instance = $this->newInstance(
			$test['A']['property'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['A'], $instance->getResults(), $info['msg'] );

		// Sample B
		$instance = $this->newInstance(
			$test['B']['property'],
			$test['cacheEnabled']
		);

		$this->assertEquals( $expected['B'], $instance->getResults(), $info['msg'] );
		$this->assertEquals( $test['cacheEnabled'], $instance->isCached() );
	}

	/**
	 * @return array
	 */
	public function exceptionDataProvider() {
		return array( array( '-Lala' ), array( '_Lila' ) );
	}

	/**
	 * @return array
	 */
	public function getCacheNonCacheDataProvider() {
		$propertyA = $this->newRandomString();
		$propertyB = $this->newRandomString();

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
