<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\Info;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Info
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InfoTest extends \PHPUnit_Framework_TestCase {

	private $apiFactory;
	private $jobQueue;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->apiFactory = $this->testEnvironment->getUtilityFactory()->newMwApiFactory();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new Info(
			$this->apiFactory->newApiMain( [] ),
			'smwinfo'
		);

		$this->assertInstanceOf(
			'SMW\MediaWiki\Api\Info',
			$instance
		);
	}

	/**
	 * @dataProvider typeDataProvider
	 */
	public function testExecuteOnStore( $queryParameters, $expectedType ) {

		$result = $this->apiFactory->doApiRequest( [
				'action' => 'smwinfo',
				'info' => $queryParameters
		] );

		if ( $expectedType === 'integer' ) {
			return $this->assertGreaterThanOrEqual( 0, $result['info'][$queryParameters] );
		}

		$this->assertInternalType(
			'array',
			$result['info'][$queryParameters]
		);
	}

	/**
	 * @dataProvider countDataProvider
	 */
	public function testExecuteOnMockStore( $statistics, $type, $expected ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getStatistics' )
			->will( $this->returnValue( $statistics ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new Info(
			$this->apiFactory->newApiMain( [ 'info' => $type ] ),
			'smwinfo'
		);

		$instance->execute();

		// MW 1.25
		$result = method_exists( $instance->getResult(), 'getResultData' ) ? $instance->getResult()->getResultData() : $instance->getResultData();

		// This came with 1.25, no idea what this suppose to be
		unset( $result['_type'] );

		$this->assertEquals(
			$expected,
			$result['info'][$type]
		);
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

		$data = $this->apiFactory->doApiRequest( [
				'action' => 'smwinfo',
				'info' => 'Foo'
		] );

		$this->assertInternalType(
			'array',
			$data['warnings']
		);
	}

	public function testJobCount() {

		$this->jobQueue->expects( $this->any() )
			->method( 'getQueueSize' )
			->will( $this->returnValue( 1 ) );

		$result = $this->apiFactory->doApiRequest(
			[
				'action' => 'smwinfo',
				'info' => 'jobcount'
			]
		);

		$this->assertArrayHasKey(
			'smw.update',
			$result['info']['jobcount']
		);
	}

	public function countDataProvider() {
		return [
			[ [ 'QUERYFORMATS' => [ 'table' => 3 ] ], 'formatcount', [ 'table' => 3 ] ],
			[ [ 'PROPUSES'     => 34 ], 'propcount',         34 ],
			[ [ 'ERRORUSES'    => 42 ], 'errorcount',        42 ],
			[ [ 'USEDPROPS'    => 51 ], 'usedpropcount',     51 ],
			[ [ 'TOTALPROPS'   => 52 ], 'totalpropcount',    52 ],
			[ [ 'DECLPROPS'    => 67 ], 'declaredpropcount', 67 ],
			[ [ 'OWNPAGE'      => 99 ], 'proppagecount',     99 ],
			[ [ 'QUERY'        => 11 ], 'querycount',        11 ],
			[ [ 'QUERYSIZE'    => 24 ], 'querysize',         24 ],
			[ [ 'CONCEPTS'     => 17 ], 'conceptcount',      17 ],
			[ [ 'SUBOBJECTS'   => 88 ], 'subobjectcount',    88 ],
		];
	}

	public function typeDataProvider() {
		return [
			[ 'proppagecount',     'integer' ],
			[ 'propcount',         'integer' ],
			[ 'errorcount',        'integer' ],
			[ 'querycount',        'integer' ],
			[ 'usedpropcount',     'integer' ],
			[ 'totalpropcount',    'integer' ],
			[ 'declaredpropcount', 'integer' ],
			[ 'conceptcount',      'integer' ],
			[ 'querysize',         'integer' ],
			[ 'subobjectcount',    'integer' ],
			[ 'formatcount',       'array'   ],
			[ 'jobcount',          'array'   ]
		];
	}

}
