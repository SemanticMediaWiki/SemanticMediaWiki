<?php

namespace SMW\Tests\MediaWiki\Api;

use SMW\MediaWiki\Api\ApiQueryResultFormatter;

use SMWQueryResult;

use ReflectionClass;

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

	public function testCanConstruct() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Api\ApiQueryResultFormatter',
			new ApiQueryResultFormatter( $queryResult )
		);
	}

	public function testSetIndexedTagNameException() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ApiQueryResultFormatter( $queryResult );
		$instance->setIsRawMode( true );

		$reflector = new ReflectionClass( '\SMW\MediaWiki\Api\ApiQueryResultFormatter' );
		$method = $reflector->getMethod( 'setIndexedTagName' );
		$method->setAccessible( true );

		$this->setExpectedException( 'InvalidArgumentException' );

		$this->assertTrue( $method->invoke( $instance, array(), null ) );
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
			->will( $this->returnValue( array() ) );

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

		$result = array(
			'results' => array(
				'Foo' => array(
					'printouts' => array( 'lula' => array( 'lila' ) )
				)
			),
			'printrequests' => array( 'Bar' ),
			'meta' => array( 'count' => 5, 'offset' => 5 )
		);

		$xml = array(
			'results' => array(
				array(
					'printouts' => array(
						array( 'label' => 'lula', 'lila', '_element' => 'value'	),
						'_element' => 'property' )
					),
					'_element' => 'subject'
				),
			'printrequests' => array(
				'Bar',
				'_element' => 'printrequest'
			),
			'meta' => array( 'count' => 5, 'offset' => 5, '_element' => 'meta' )
		);

		$provider = array();

		// #0 Without further results
		$provider[] = array(
			array(
				'result'  => $result,
				'rawMode' => false,
				'furtherResults' => false
			),
			array(
				'result' => $result,
				'continueOffset' => false
			)
		);

		// #1 Without further results + XML
		$provider[] = array(
			array(
				'result'  => $result,
				'rawMode' => true,
				'furtherResults' => false
			),
			array(
				'result' => $xml,
				'continueOffset' => false
			)
		);

		// #2 With further results
		$provider[] = array(
			array(
				'result' => $result,
				'rawMode' => false,
				'furtherResults' => true
			),
			array(
				'result' => $result,
				'continueOffset' => 10
			)
		);

		// #3 With further results + XML
		$provider[] = array(
			array(
				'result' => $result,
				'rawMode' => true,
				'furtherResults' => true
			),
			array(
				'result' => $xml,
				'continueOffset' => 10
			)
		);


		// #4 Extended subject data + XML
		$provider[] = array(
			array(
				'result' => array(
					'results' => array(
						'Foo' => array(
							'printouts' => array(
								'lula' => array( 'lila' ) ),
							'fulltext' => 'Foo' )
						),
					'printrequests' => array( 'Bar' ),
					'meta' => array(
						'count' => 5,
						'offset' => 5
					)
				),
				'rawMode' => true,
				'furtherResults' => true
			),
			array(
				'result' =>  array(
					'results' => array(
						array(
							'printouts' => array(
								array(
									'label' => 'lula',
									'lila', '_element' => 'value'
								), '_element' => 'property' ),
							'fulltext' => 'Foo'
							), '_element' => 'subject'
						),
					'printrequests' => array( 'Bar', '_element' => 'printrequest' ),
					'meta' => array(
						'count' => 5,
						'offset' => 5,
						'_element' => 'meta'
					)
				),
				'continueOffset' => 10
			)
		);

		// #5 printouts without values + XML
		$provider[] = array(
			array(
				'result' => array(
					'results' => array(
						'Foo' => array(
							'printouts' => array( 'lula' ),
							'fulltext' => 'Foo' )
						),
					'printrequests' => array( 'Bar' ),
					'meta' => array(
						'count' => 5,
						'offset' => 5
					)
				),
				'rawMode' => true,
				'furtherResults' => true
			),
			array(
				'result' =>  array(
					'results' => array(
						array(
							'printouts' => array( '_element' => 'property' ),
							'fulltext' => 'Foo'
							),
						'_element' => 'subject'
						),
					'printrequests' => array( 'Bar', '_element' => 'printrequest' ),
					'meta' => array(
						'count' => 5,
						'offset' => 5,
						'_element' => 'meta'
					)
				),
				'continueOffset' => 10
			)
		);

		// #6 empty results + XML
		$provider[] = array(
			array(
				'result' => array(
					'results' => array(),
					'printrequests' => array( 'Bar' ),
					'meta' => array(
						'count' => 0,
						'offset' => 0
					)
				),
				'rawMode' => true,
				'furtherResults' => false
			),
			array(
				'result' =>  array(
					'results' => array(),
					'printrequests' => array( 'Bar', '_element' => 'printrequest' ),
					'meta' => array(
						'count' => 0,
						'offset' => 0,
						'_element' => 'meta'
					)
				),
				'continueOffset' => 0
			)
		);

		return $provider;
	}

	public function errorDataProvider() {

		$errors = array( 'Foo', 'Bar' );

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'rawMode'=> false,
				'errors' => $errors
			),
			array(
				'query' => $errors
			)
		);

		// #1
		$provider[] = array(
			array(
				'rawMode'=> true,
				'errors' => $errors
			),
			array(
				'query' => array_merge( $errors, array( '_element' => 'info' ) )
			)
		);

		return $provider;
	}
}
