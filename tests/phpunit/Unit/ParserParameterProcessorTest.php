<?php

namespace SMW\Tests;

use SMW\ParserParameterProcessor;

/**
 * @covers \SMW\ParserParameterProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserParameterProcessorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider parametersDataProvider
	 */
	public function testCanConstruct( array $parameters ) {

		$this->assertInstanceOf(
			'SMW\ParserParameterProcessor',
			new ParserParameterProcessor( $parameters )
		);
	}

	/**
	 * @dataProvider parametersDataProvider
	 */
	public function testGetRaw( array $parameters ) {

		$instance = new ParserParameterProcessor( $parameters );

		$this->assertEquals(
			$parameters,
			$instance->getRaw()
		);
	}

	public function testSetParameters() {

		$instance = new ParserParameterProcessor();

		$parameters = array(
			'Foo' => 'Bar'
		);

		$instance->setParameters( $parameters );

		$this->assertEquals(
			$parameters,
			$instance->toArray()
		);
	}

	public function testAddParameter() {

		$instance = new ParserParameterProcessor();

		$instance->addParameter(
			'Foo', 'Bar'
		);

		$this->assertEquals(
			array( 'Foo' => array( 'Bar' ) ),
			$instance->toArray()
		);
	}

	public function testSetParameter() {

		$instance = new ParserParameterProcessor();

		$instance->setParameter(
			'Foo', array()
		);

		$this->assertEmpty(
			$instance->toArray()
		);

		$instance->setParameter(
			'Foo', array( 'Bar' )
		);

		$this->assertEquals(
			array( 'Foo' => array( 'Bar' ) ),
			$instance->toArray()
		);
	}

	/**
	 * @dataProvider parametersDataProvider
	 */
	public function testToArray( array $parameters, array $expected ) {

		$instance = new ParserParameterProcessor( $parameters );

		$this->assertEquals(
			$expected,
			$instance->toArray()
		);
	}

	/**
	 * @dataProvider firstParameterDataProvider
	 */
	public function testGetFirst( array $parameters, array $expected ) {

		$instance = new ParserParameterProcessor( $parameters );

		$this->assertEquals(
			$expected['identifier'],
			$instance->getFirst()
		);
	}

	public function parametersDataProvider() {
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

	public function firstParameterDataProvider() {
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
