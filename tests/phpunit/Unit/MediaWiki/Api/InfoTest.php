<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Api\Info;
use SMW\Tests\Utils\MwApiFactory;

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

		$instance = new Info(
			$this->apiFactory->newApiMain( array() ),
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

		$result = $this->apiFactory->doApiRequest( array(
				'action' => 'smwinfo',
				'info' => $queryParameters
		) );

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

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new Info(
			$this->apiFactory->newApiMain( array( 'info' => $type ) ),
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

		$data = $this->apiFactory->doApiRequest( array(
				'action' => 'smwinfo',
				'info' => 'Foo'
		) );

		$this->assertInternalType(
			'array',
			$data['warnings']
		);
	}

	public function countDataProvider() {
		return array(
			array( array( 'QUERYFORMATS' => array( 'table' => 3 ) ), 'formatcount', array( 'table' => 3 ) ),
			array( array( 'PROPUSES'     => 34 ), 'propcount',         34 ),
			array( array( 'ERRORUSES'    => 42 ), 'errorcount',        42 ),
			array( array( 'USEDPROPS'    => 51 ), 'usedpropcount',     51 ),
			array( array( 'DECLPROPS'    => 67 ), 'declaredpropcount', 67 ),
			array( array( 'OWNPAGE'      => 99 ), 'proppagecount',     99 ),
			array( array( 'QUERY'        => 11 ), 'querycount',        11 ),
			array( array( 'QUERYSIZE'    => 24 ), 'querysize',         24 ),
			array( array( 'CONCEPTS'     => 17 ), 'conceptcount',      17 ),
			array( array( 'SUBOBJECTS'   => 88 ), 'subobjectcount',    88 ),
		);
	}

	public function typeDataProvider() {
		return array(
			array( 'proppagecount',     'integer' ),
			array( 'propcount',         'integer' ),
			array( 'errorcount',        'integer' ),
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
