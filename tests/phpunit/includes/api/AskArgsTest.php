<?php

namespace SMW\Test;

use SMW\Api\AskArgs;

/**
 * @covers \SMW\Api\AskArgs
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
class AskArgsTest extends ApiTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\Api\AskArgs';
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * This test only verifies if either an error result or
	 * a "normal" query result array is returned. The test makes
	 * only an assymptions about a predefinied property
	 * "Modification date" printrequests
	 *
	 * @since 1.9
	 */
	public function testExecuteOnSQLStore( array $query, array $expected ) {

		$this->runOnlyOnSQLStore();

		$results = $this->doApiRequest( array(
			'action'     => 'askargs',
			'conditions' => $query['conditions'],
			'printouts'  => $query['printouts'],
			'parameters' => $query['parameters'],
		) );

		$this->assertInternalType( 'array', $results );

		if ( isset( $expected['error'] ) ) {
			$this->assertArrayHasKey( 'error', $results );
		} else {
			$this->assertEquals( $expected, $results['query']['printrequests'] );
		}

	}

	/**
	 * Test against a mock store to ensure that methods are executed
	 * regardless whether a "real" Store is available or not
	 *
	 * @since 1.9
	 */
	public function testExecuteOnMockStore() {

		$requestParameters = array(
			'conditions' => 'Foo::+',
			'printouts'  => 'Bar',
			'parameters' => 'sort=asc'
		);

		$expected = array(
			'query-continue-offset' => 10,
			'query' => array(
				'results' => array(
					'Foo' => array(
						'printouts' => array( 'lula' => array( 'lila' ) )
					)
				),
				'printrequests' => array( 'Bar' ),
				'meta' => array( 'count' => 5, 'offset' => 5 )
			)
		);

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getQueryResult' => array( $this, 'mockStoreQueryResultCallback' )
		) );

		$api = new AskArgs( $this->getApiMain( $requestParameters ), 'askargs' );
		$api->withContext()->getDependencyBuilder()->getContainer()->registerObject( 'Store', $mockStore );
		$api->execute();

		$result = $api->getResultData();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Use a callback injection to control the return value of the
	 * induced mock object
	 *
	 * @return SMWQueryResult
	 */
	public function mockStoreQueryResultCallback( $query ) {

		$result = '';

		if ( $query->getQueryString() === '[[Foo::+]]' ) {
			$result = array(
				'results' => array(
					'Foo' => array(
						'printouts' => array( 'lula' => array( 'lila' ) )
					)
				),
				'printrequests' => array( 'Bar' ),
				'meta' => array( 'count' => 5, 'offset' => 5 )
			);
		}

		return $this->newMockBuilder()->newObject( 'QueryResult', array(
			'toArray'           => $result,
			'hasFurtherResults' => true
		) );

	}

	/**
	 * Provides a query array and its expected printrequest array
	 *
	 * @return array
	 */
	public function queryDataProvider() {
		return array(

			// #0 Query producing an error result
			array(
				array(
					'conditions' => '[[Modification date::+]]',
					'printouts'  => null,
					'parameters' => null
				),
				array(
					'error'      => true
				)
			),

			// #1 Query producing an error result
			array(
				array(
					'conditions' => '[[Modification date::+]]',
					'printouts'  => null,
					'parameters' => 'limit=10'
				),
				array(
					'error'      => true
				)
			),

			// #2 Query producing an error result
			array(
				array(
					'conditions' => '[[Modification date::+]]',
					'printouts'  => 'Modification date',
					'parameters' => 'limit=10'
				),
				array(
					'error'      => true
				)
			),

			// #3 Query producing a return result
			array(
				array(
					'conditions' => 'Modification date::+',
					'printouts'  => null,
					'parameters' => null
				),
				array(
					array(
						'label'=> '',
						'typeid' => '_wpg',
						'mode' => 2,
						'format' => false
					)
				)
			),

			// #4 Query producing a return result
			array(
				array(
					'conditions' => 'Modification date::+',
					'printouts'  => 'Modification date',
					'parameters' => null
				),
				array(
					array(
						'label'=> '',
						'typeid' => '_wpg',
						'mode' => 2,
						'format' => false
					),
					array(
						'label'=> 'Modification date',
						'typeid' => '_dat',
						'mode' => 1,
						'format' => ''
					)
				)
			),

			// #5 Query producing a return result
			array(
				array(
					'conditions' => 'Modification date::+',
					'printouts'  => 'Modification date',
					'parameters' => 'limit=1'
				),
				array(
					array(
						'label'=> '',
						'typeid' => '_wpg',
						'mode' => 2,
						'format' => false
					),
					array(
						'label'=> 'Modification date',
						'typeid' => '_dat',
						'mode' => 1,
						'format' => ''
					)
				)
			),
		);
	}
}
