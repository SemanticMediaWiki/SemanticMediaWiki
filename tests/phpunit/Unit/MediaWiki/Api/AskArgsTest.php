<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\Tests\Utils\MwApiFactory;

use SMW\MediaWiki\Api\AskArgs;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\MediaWiki\Api\AskArgs
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class AskArgsTest extends \PHPUnit_Framework_TestCase  {

	private $apiFactory;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->apiFactory = new MwApiFactory();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		ApplicationFactory::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new AskArgs(
			$this->apiFactory->newApiMain( array() ),
			'askargs'
		);

		$this->assertInstanceOf(
			'SMW\MediaWiki\Api\AskArgs',
			$instance
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testExecuteOnStore( array $query, array $expected ) {

		$results = $this->apiFactory->doApiRequest( array(
			'action'     => 'askargs',
			'conditions' => $query['conditions'],
			'printouts'  => $query['printouts'],
			'parameters' => $query['parameters'],
		) );

		$this->assertInternalType( 'array', $results );

		if ( isset( $expected['error'] ) ) {
			return $this->assertArrayHasKey( 'error', $results );
		}

		$this->assertEquals(
			$expected,
			$results['query']['printrequests']
		);
	}

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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnCallback( array( $this, 'mockStoreQueryResultCallback' ) ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new AskArgs(
			$this->apiFactory->newApiMain( $requestParameters ),
			'askargs'
		);

		$instance->execute();

		// MW 1.25
		$result = method_exists( $instance->getResult(), 'getResultData' ) ? $instance->getResult()->getResultData() : $instance->getResultData();

		// This came with 1.25, no idea what this suppose to be
		unset( $result['_type'] );

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );
	}

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

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'toArray' )
			->will( $this->returnValue( $result ) );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( true ) );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( array() ) );

		return $queryResult;
	}

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
