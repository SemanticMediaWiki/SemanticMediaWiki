<?php

namespace SMW\Test;

use SMW\Api\Info;

/**
 * @covers \SMW\Api\Info
 * @covers \SMW\Api\Base
 *
 * @group SMW
 * @group SMWExtension
 * @group API
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InfoTest extends ApiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Api\Info';
	}

	/**
	 * @dataProvider typeDataProvider
	 *
	 * @since 1.9
	 */
	public function testExecuteOnSQLStore( $queryParameters, $expectedType ) {

		$this->runOnlyOnSQLStore();

		$result = $this->doApiRequest( array(
				'action' => 'smwinfo',
				'info' => $queryParameters
		) );

		// Info array should return with either 0 or > 0 for integers
		if ( $expectedType === 'integer' ) {
			$this->assertGreaterThanOrEqual( 0, $result['info'][$queryParameters] );
		} else {
			$this->assertInternalType( 'array', $result['info'][$queryParameters] );
		}

	}

	/**
	 * @dataProvider countDataProvider
	 *
	 * Test against a mock store to ensure that methods are executed
	 * regardless whether a "real" Store is available or not
	 *
	 * @since 1.9
	 */
	public function testExecuteOnMockStore( $test, $type, $expected ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getStatistics' => $test
		) );

		$api = new Info( $this->getApiMain( array( 'info' => $type ) ), 'smwinfo' );
		$api->withContext()->getDependencyBuilder()->getContainer()->registerObject( 'Store', $mockStore );
		$api->execute();

		$result = $api->getResultData();

		$this->assertEquals( $expected, $result['info'][ $type ] );
	}

	/**
	 * Test unknown query parameter
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
