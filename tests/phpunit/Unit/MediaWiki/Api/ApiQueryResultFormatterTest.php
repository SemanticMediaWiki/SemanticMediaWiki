<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\ApiQueryResultFormatter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Api\ApiQueryResultFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ApiQueryResultFormatterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Api\ApiQueryResultFormatter',
			new ApiQueryResultFormatter( $queryResult )
		);
	}

	public function testInvalidSetIndexedTagNameThrowsException() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ApiQueryResultFormatter( $queryResult );
		$instance->setIsRawMode( true );

		// Used to work in PHP 5 but not with PHP 7, made the
		// method public to test the exception without reflection
		// $reflector = new ReflectionClass( 'SMW\MediaWiki\Api\ApiQueryResultFormatter' );
		// $method = $reflector->getMethod( 'setIndexedTagName' );
		// $method->setAccessible( true );
		// $method->invoke( $instance, $arr, null )

		$arr = [];

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->setIndexedTagName( $arr, null );
	}

	/**
	 * @dataProvider resultDataProvider
	 */
	public function testResultFormat( array $parameters, array $expected ) {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'toArray' )
			->will( $this->returnValue( $parameters['result'] ) );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( $parameters['furtherResults'] ) );

		$instance = new ApiQueryResultFormatter( $queryResult );

		$instance->setIsRawMode( $parameters['rawMode'] );
		$instance->doFormat();

		$this->assertEquals( 'query', $instance->getType() );

		$this->assertEquals(
			$expected['result'],
			$instance->getResult()
		);

		$this->assertEquals(
			$expected['continueOffset'],
			$instance->getContinueOffset()
		);
	}

	/**
	 * @dataProvider errorDataProvider
	 */
	public function testErrorFormat( array $parameters, array $expected ) {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( $parameters['errors'] ) );

		$instance = new ApiQueryResultFormatter( $queryResult );

		$instance->setIsRawMode( $parameters['rawMode'] );
		$instance->doFormat();

		$this->assertEquals( 'error', $instance->getType() );

		$this->assertEquals(
			$expected,
			$instance->getResult()
		);
	}

	public function resultDataProvider() {

		$result = [
			'results' => [
				'Foo' => [
					'printouts' => [ 'lula' => [ 'lila' ] ]
				]
			],
			'printrequests' => [ 'Bar' ],
			'meta' => [ 'count' => 5, 'offset' => 5 ]
		];

		$xml = [
			'results' => [
				[
					'printouts' => [
						[ 'label' => 'lula', 'lila', '_element' => 'value'	],
						'_element' => 'property' ]
					],
					'_element' => 'subject'
				],
			'printrequests' => [
				'Bar',
				'_element' => 'printrequest'
			],
			'meta' => [ 'count' => 5, 'offset' => 5, '_element' => 'meta' ]
		];

		$provider = [];

		// #0 Without further results
		$provider[] = [
			[
				'result'  => $result,
				'rawMode' => false,
				'furtherResults' => false
			],
			[
				'result' => $result,
				'continueOffset' => false
			]
		];

		// #1 Without further results + XML
		$provider[] = [
			[
				'result'  => $result,
				'rawMode' => true,
				'furtherResults' => false
			],
			[
				'result' => $xml,
				'continueOffset' => false
			]
		];

		// #2 With further results
		$provider[] = [
			[
				'result' => $result,
				'rawMode' => false,
				'furtherResults' => true
			],
			[
				'result' => $result,
				'continueOffset' => 10
			]
		];

		// #3 With further results + XML
		$provider[] = [
			[
				'result' => $result,
				'rawMode' => true,
				'furtherResults' => true
			],
			[
				'result' => $xml,
				'continueOffset' => 10
			]
		];


		// #4 Extended subject data + XML
		$provider[] = [
			[
				'result' => [
					'results' => [
						'Foo' => [
							'printouts' => [
								'lula' => [ 'lila' ] ],
							'fulltext' => 'Foo' ]
						],
					'printrequests' => [ 'Bar' ],
					'meta' => [
						'count' => 5,
						'offset' => 5
					]
				],
				'rawMode' => true,
				'furtherResults' => true
			],
			[
				'result' =>  [
					'results' => [
						[
							'printouts' => [
								[
									'label' => 'lula',
									'lila', '_element' => 'value'
								], '_element' => 'property' ],
							'fulltext' => 'Foo'
							], '_element' => 'subject'
						],
					'printrequests' => [ 'Bar', '_element' => 'printrequest' ],
					'meta' => [
						'count' => 5,
						'offset' => 5,
						'_element' => 'meta'
					]
				],
				'continueOffset' => 10
			]
		];

		// #5 printouts without values + XML
		$provider[] = [
			[
				'result' => [
					'results' => [
						'Foo' => [
							'printouts' => [ 'lula' ],
							'fulltext' => 'Foo' ]
						],
					'printrequests' => [ 'Bar' ],
					'meta' => [
						'count' => 5,
						'offset' => 5
					]
				],
				'rawMode' => true,
				'furtherResults' => true
			],
			[
				'result' =>  [
					'results' => [
						[
							'printouts' => [ '_element' => 'property' ],
							'fulltext' => 'Foo'
							],
						'_element' => 'subject'
						],
					'printrequests' => [ 'Bar', '_element' => 'printrequest' ],
					'meta' => [
						'count' => 5,
						'offset' => 5,
						'_element' => 'meta'
					]
				],
				'continueOffset' => 10
			]
		];

		// #6 empty results + XML
		$provider[] = [
			[
				'result' => [
					'results' => [],
					'printrequests' => [ 'Bar' ],
					'meta' => [
						'count' => 0,
						'offset' => 0
					]
				],
				'rawMode' => true,
				'furtherResults' => false
			],
			[
				'result' =>  [
					'results' => [],
					'printrequests' => [ 'Bar', '_element' => 'printrequest' ],
					'meta' => [
						'count' => 0,
						'offset' => 0,
						'_element' => 'meta'
					]
				],
				'continueOffset' => 0
			]
		];

		return $provider;
	}

	public function errorDataProvider() {

		$errors = [ 'Foo', 'Bar' ];

		$provider = [];

		// #0
		$provider[] = [
			[
				'rawMode'=> false,
				'errors' => $errors
			],
			[
				'query' => $errors
			]
		];

		// #1
		$provider[] = [
			[
				'rawMode'=> true,
				'errors' => $errors
			],
			[
				'query' => array_merge( $errors, [ '_element' => 'info' ] )
			]
		];

		return $provider;
	}
}
