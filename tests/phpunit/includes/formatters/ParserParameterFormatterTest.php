<?php

namespace SMW\Test;

use SMW\ParserParameterFormatter;

/**
 * Tests for the ParserParameterFormatter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ParserParameterFormatter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class ParserParameterFormatterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParserParameterFormatter';
	}

	/**
	 * Helper method that returns a ParserParameterFormatter object
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return ParserParameterFormatter
	 */
	private function getInstance( array $params ) {
		return new ParserParameterFormatter( $params );
	}

	/**
	 * @test ParserParameterFormatter::__construct
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 */
	public function testConstructor( array $params ) {
		$instance = $this->getInstance( $params );
		$this->assertInstanceOf( 'SMW\ParserParameterFormatter', $instance );
	}

	/**
	 * @test ParserParameterFormatter::getRaw
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 */
	public function testGetRaw( array $params ) {
		$instance = $this->getInstance( $params );
		$this->assertEquals( $params, $instance->getRaw() );
	}

	/**
	 * @test ParserParameterFormatter::setParameters
	 *
	 * @since 1.9
	 */
	public function testSetParameters() {
		$instance = $this->getInstance( array() );
		$parameters = array( 'Foo' => 'Bar' );

		$instance->setParameters( $parameters );
		$this->assertEquals( $parameters, $instance->toArray() );
	}

	/**
	 * @test ParserParameterFormatter::addParameter
	 *
	 * @since 1.9
	 */
	public function testAddParameter() {
		$instance = $this->getInstance( array() );
		$instance->addParameter( 'Foo', 'Bar' );

		$this->assertEquals( array( 'Foo' =>array( 'Bar' ) ), $instance->toArray() );
	}

	/**
	 * @test ParserParameterFormatter::__construct (Test instance exception)
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 */
	public function testConstructorException( array $params ) {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = $this->getInstance();
	}

	/**
	 * @test ParserParameterFormatter::toArray
	 * @dataProvider getParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testToArray( array $params, array $expected ) {
		$instance = $this->getInstance( $params );
		$results = $instance->toArray();

		$this->assertTrue( is_array( $results ) );
		$this->assertEquals( $expected, $results);
	}

	/**
	 * @test ParserParameterFormatter::getFirst
	 * @dataProvider getFirstDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetFirst( array $params, array $expected ) {
		$results = $this->getInstance( $params );
		$this->assertEquals( $expected['identifier'], $results->getFirst() );
	}

	/**
	 * Provides sample data of parameter combinations
	 *
	 * @return array
	 */
	public function getParametersDataProvider() {
		return array(
			// {{#...:
			// |Has test 1=One
			// }}
			array(
				array(
					'Has test 1=One'
				),
				array(
					'Has test 1' => array( 'One' )
				)
			),

			// {{#...:
			// |Has test 1=One
			// }}
			array(
				array(
					array( 'Foo' ),
					'Has test 1=One',
				),
				array(
					'Has test 1' => array( 'One' )
				),
				array(
					'msg' => 'Failed to recognize that only strings can be processed'
				)
			),

			// {{#...:
			// |Has test 2=Two
			// |Has test 2=Three;Four|+sep=;
			// }}
			array(
				array(
					'Has test 2=Two',
					'Has test 2=Three;Four',
					'+sep=;'
				),
				array(
					'Has test 2' => array( 'Two', 'Three', 'Four' )
				)
			),

			// {{#...:
			// |Has test 3=One,Two,Three|+sep
			// |Has test 4=Four
			// }}
			array(
				array(
					'Has test 3=One,Two,Three',
					'+sep',
					'Has test 4=Four'
				),
				array(
					'Has test 3' => array( 'One', 'Two', 'Three' ),
					'Has test 4' => array( 'Four' )
				)
			),

			// {{#...:
			// |Has test 5=Test 5-1|Test 5-2|Test 5-3|Test 5-4
			// |Has test 5=Test 5-5
			// }}
			array(
				array(
					'Has test 5=Test 5-1',
					'Test 5-2',
					'Test 5-3',
					'Test 5-4',
					'Has test 5=Test 5-5'
				),
				array(
					'Has test 5' => array( 'Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', 'Test 5-5' )
				)
			),

			// {{#...:
			// |Has test 6=1+2+3|+sep=+
			// |Has test 7=7
			// |Has test 8=9,10,11,|+sep=
			// }}
			array(
				array(
					'Has test 6=1+2+3',
					'+sep=+',
					'Has test 7=7',
					'Has test 8=9,10,11,',
					'+sep='
				),
				array(
					'Has test 6' => array( '1', '2', '3'),
					'Has test 7' => array( '7' ),
					'Has test 8' => array( '9', '10', '11' )
				)
			),

			// {{#...:
			// |Has test 9=One,Two,Three|+sep=;
			// |Has test 10=Four
			// }}
			array(
				array(
					'Has test 9=One,Two,Three',
					'+sep=;',
					'Has test 10=Four'
				),
				array(
					'Has test 9' => array( 'One,Two,Three' ),
					'Has test 10' => array( 'Four' )
				)
			),

			// {{#...:
			// |Has test 11=Test 5-1|Test 5-2|Test 5-3|Test 5-4
			// |Has test 12=Test 5-5
			// |Has test 11=9,10,11,|+sep=
			// }}
			array(
				array(
					'Has test 11=Test 5-1',
					'Test 5-2',
					'Test 5-3',
					'Test 5-4',
					'Has test 12=Test 5-5',
					'Has test 11=9,10,11,',
					'+sep='
				),
				array(
					'Has test 11' => array( 'Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', '9', '10', '11' ),
					'Has test 12' => array( 'Test 5-5' )
				)
			),

			// {{#...:
			// |Has test url=http://www.semantic-mediawiki.org/w/index.php?title=Subobject;http://www.semantic-mediawiki.org/w/index.php?title=Set|+sep=;
			// }}
			array(
				array(
					'Has test url=http://www.semantic-mediawiki.org/w/index.php?title=Subobject;http://www.semantic-mediawiki.org/w/index.php?title=Set',
					'+sep=;'
				),
				array(
					'Has test url' => array( 'http://www.semantic-mediawiki.org/w/index.php?title=Subobject', 'http://www.semantic-mediawiki.org/w/index.php?title=Set' )
				)
			),

		);
	}

	/**
	 * Provides sample data of first parameter combinations
	 *
	 * @return array
	 */
	public function getFirstDataProvider() {
		return array(
			// {{#subobject:
			// |Has test 1=One
			// }}
			array(
				array( '', 'Has test 1=One'),
				array( 'identifier' => null )
			),

			// {{#set_recurring_event:Foo
			// |Has test 2=Two
			// |Has test 2=Three;Four|+sep=;
			// }}
			array(
				array( 'Foo' , 'Has test 2=Two', 'Has test 2=Three;Four', '+sep=;' ),
				array( 'identifier' => 'Foo' )
			),

			// {{#subobject:-
			// |Has test 2=Two
			// |Has test 2=Three;Four|+sep=;
			// }}
			array(
				array( '-', 'Has test 2=Two', 'Has test 2=Three;Four', '+sep=;' ),
				array( 'identifier' => '-' )
			),
		);
	}
}
