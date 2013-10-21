<?php

namespace SMW\Test;

use SMW\ApiQueryResultFormatter;

use SMWQueryResult;

/**
 * @covers \SMW\ApiQueryResultFormatter
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ApiQueryResultFormatterTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ApiQueryResultFormatter';
	}

	/**
	 * @since 1.9
	 *
	 * @return ApiQueryResultFormatter
	 */
	private function newInstance( SMWQueryResult $queryResult = null ) {

		if ( $queryResult === null ) {
			$queryResult = $this->newMockBuilder()->newObject( 'QueryResult' );
		}

		return new ApiQueryResultFormatter( $queryResult );
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
	public function testSetIndexedTagNameException() {

		$this->SetExpectedException( 'InvalidArgumentException' );

		$instance = $this->newInstance();
		$instance->setIsRawMode( true );

		$reflector = $this->newReflector();
		$method = $reflector->getMethod( 'setIndexedTagName' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $instance, array(), null ) );

	}

	/**
	 * @dataProvider resultDataProvider
	 *
	 * @since 1.9
	 */
	public function testResultFormat( array $test, array $expected ) {

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', array(
			'toArray'           => $test['result'],
			'getErrors'         => array(),
			'hasFurtherResults' => $test['furtherResults']
		) );

		$instance = $this->newInstance( $queryResult );
		$instance->setIsRawMode( $test['rawMode'] );
		$instance->runFormatter();

		$this->assertEquals( 'query', $instance->getType() );
		$this->assertEquals( $expected['result'], $instance->getResult() );
		$this->assertEquals( $expected['continueOffset'], $instance->getContinueOffset() );

	}

	/**
	 * @dataProvider errorDataProvider
	 *
	 * @since 1.9
	 */
	public function testErrorFormat( array $test, array $expected ) {

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', array(
			'toArray'           => array(),
			'getErrors'         => $test['errors'],
			'hasFurtherResults' => false
		) );

		$instance = $this->newInstance( $queryResult );

		$instance->setIsRawMode( $test['rawMode'] );
		$instance->runFormatter();

		$this->assertEquals( 'error', $instance->getType() );
		$this->assertEquals( $expected, $instance->getResult() );

	}

	/**
	 * @return array
	 */
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

	/**
	 * @return array
	 */
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
