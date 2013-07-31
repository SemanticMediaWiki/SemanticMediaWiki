<?php

namespace SMW\Test;

use SMW\ApiInfo;

/**
 * Tests for the ApiInfo class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ApiInfo
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 */
class ApiInfoTest extends ApiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ApiInfo';
	}

	/**
	 * @test ApiInfo::execute
	 * @dataProvider typeDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $queryParameters
	 * @param array $expectedType
	 */
	public function testExecuteOnStore( $queryParameters, $expectedType ) {

		$result = $this->doApiRequest( array(
				'action' => 'smwinfo',
				'info' => $queryParameters
		) );

		// Works only after SMW\StatisticsAggregator is available
		//$this->assertInternalType( $expectedType, $result['info'][$queryParameters] );

		// Info array should return with either 0 or > 0 for integers
		if ( $expectedType === 'integer' ) {
			$this->assertGreaterThanOrEqual( 0, $result['info'][$queryParameters] );
		} else {
			$this->assertInternalType( 'array', $result['info'][$queryParameters] );
		}

	}

	/**
	 * @test ApiInfo::execute
	 * @dataProvider countDataProvider
	 *
	 * Test against a mock store to ensure that methods are executed
	 * regardless whether a "real" Store is available or not
	 *
	 * @since 1.9
	 *
	 * @param array $test
	 * @param string $type
	 * @param array $expected
	 */
	public function testExecuteOnMockStore( $test, $type, $expected ) {

		$mockStore = $this->newMockObject( array(
			'getStatistics' => $test
		) )->getMockStore();

		$api = new ApiInfo( $this->getApiMain( array( 'info' => $type ) ), 'smwinfo' );
		$api->setStore( $mockStore );
		$api->execute();

		$result = $api->getResultData();

		$this->assertEquals( $expected, $result['info'][ $type ] );
	}

	/**
	 * @test ApiInfo::execute (Test unknown query parameter)
	 *
	 * Only valid parameters will yield an info array while an unknown parameter
	 * will produce a "warnings" array.
	 *
	 * @since 1.9
	 */
	public function testUnknownQueryParameter() {

		$data = $this->doApiRequest( array(
				'action' => 'smwinfo',
				'info' => 'Foo'
		) );

		$this->assertInternalType( 'array', $data['warnings'] );

	}

	/**
	 * Verify count and mapping results
	 *
	 * @return array
	 */
	public function countDataProvider() {
		return array(
			array( array( 'QUERYFORMATS' => array( 'table' => 3 ) ), 'formatcount', array( 'table' => 3 ) ),
			array( array( 'PROPUSES'     => 34 ), 'propcount',         34 ),
			array( array( 'USEDPROPS'    => 51 ), 'usedpropcount',     51 ),
			array( array( 'DECLPROPS'    => 67 ), 'declaredpropcount', 67 ),
			array( array( 'OWNPAGE'      => 99 ), 'proppagecount',     99 ),
			array( array( 'QUERY'        => 11 ), 'querycount',        11 ),
			array( array( 'QUERYSIZE'    => 24 ), 'querysize',         24 ),
			array( array( 'CONCEPTS'     => 17 ), 'conceptcount',      17 ),
			array( array( 'SUBOBJECTS'   => 88 ), 'subobjectcount',    88 ),
		);
	}

	/**
	 * Verify types
	 *
	 * @return array
	 */
	public function typeDataProvider() {
		return array(
			array( 'proppagecount',     'integer' ),
			array( 'propcount',         'integer' ),
			array( 'querycount',        'integer' ),
			array( 'usedpropcount',     'integer' ),
			array( 'declaredpropcount', 'integer' ),
			array( 'conceptcount',      'integer' ),
			array( 'querysize',         'integer' ),
			array( 'subobjectcount',    'integer' ),
			array( 'formatcount',       'array'   )
		);
	}
}
