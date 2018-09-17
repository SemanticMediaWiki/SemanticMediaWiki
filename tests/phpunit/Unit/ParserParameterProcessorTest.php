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

		$parameters = [
			'Foo' => 'Bar'
		];

		$instance->setParameters( $parameters );

		$this->assertEquals(
			$parameters,
			$instance->toArray()
		);
	}

	public function testAddAndRemoveParameter() {

		$instance = new ParserParameterProcessor();

		$instance->addParameter(
			'Foo', 'Bar'
		);

		$this->assertEquals(
			[ 'Foo' => [ 'Bar' ] ],
			$instance->toArray()
		);

		$instance->removeParameterByKey( 'Foo' );

		$this->assertFalse(
			$instance->hasParameter( 'Foo' )
		);
	}

	public function testSetParameter() {

		$instance = new ParserParameterProcessor();

		$instance->setParameter(
			'Foo', []
		);

		$this->assertEmpty(
			$instance->toArray()
		);

		$instance->setParameter(
			'Foo', [ 'Bar' ]
		);

		$this->assertEquals(
			[ 'Foo' => [ 'Bar' ] ],
			$instance->toArray()
		);
	}

	public function testSort() {

		$a = [
			'Has test 3=One,Two,Three',
			'+sep',
			'Has test 4=Four'
		];

		$instance = new ParserParameterProcessor(
			$a
		);

		$paramsA = $instance->toArray();
		$instance->sort( $paramsA );

		$b = [
			'Has test 4=Four',
			'Has test 3=Two,Three,One',
			'+sep',
		];

		$instance = new ParserParameterProcessor(
			$b
		);

		$paramsB = $instance->toArray();

		$instance->sort( $paramsB );

		$this->assertEquals(
			$paramsA,
			$paramsB
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

		// {{#...:
		// |Has test 1=One
		// }}
		$provider[] = [
			[
				'Has test 1=One'
			],
			[
				'Has test 1' => [ 'One' ]
			]
		];

		// {{#...:
		// |Has test 1=One
		// }}
		$provider[] = [
			[
				[ 'Foo' ],
				'Has test 1=One',
			],
			[
				'Has test 1' => [ 'One' ]
			],
			[
				'msg' => 'Failed to recognize that only strings can be processed'
			]
		];

		// {{#...:
		// |Has test 2=Two
		// |Has test 2=Three;Four|+sep=;
		// }}
		$provider[] = [
			[
				'Has test 2=Two',
				'Has test 2=Three;Four',
				'+sep=;'
			],
			[
				'Has test 2' => [ 'Two', 'Three', 'Four' ]
			]
		];

		// {{#...:
		// |Has test 3=One,Two,Three|+sep
		// |Has test 4=Four
		// }}
		$provider[] = [
			[
				'Has test 3=One,Two,Three',
				'+sep',
				'Has test 4=Four'
			],
			[
				'Has test 3' => [ 'One', 'Two', 'Three' ],
				'Has test 4' => [ 'Four' ]
			]
		];

		// {{#...:
		// |Has test 5=Test 5-1|Test 5-2|Test 5-3|Test 5-4
		// |Has test 5=Test 5-5
		// }}
		$provider[] = [
			[
				'Has test 5=Test 5-1',
				'Test 5-2',
				'Test 5-3',
				'Test 5-4',
				'Has test 5=Test 5-5'
			],
			[
				'Has test 5' => [ 'Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', 'Test 5-5' ]
			]
		];

		// {{#...:
		// |Has test 6=1+2+3|+sep=+
		// |Has test 7=7
		// |Has test 8=9,10,11,|+sep=
		// }}
		$provider[] = [
			[
				'Has test 6=1+2+3',
				'+sep=+',
				'Has test 7=7',
				'Has test 8=9,10,11,',
				'+sep='
			],
			[
				'Has test 6' => [ '1', '2', '3'],
				'Has test 7' => [ '7' ],
				'Has test 8' => [ '9', '10', '11' ]
			]
		];

		// {{#...:
		// |Has test 9=One,Two,Three|+sep=;
		// |Has test 10=Four
		// }}
		$provider[] = [
			[
				'Has test 9=One,Two,Three',
				'+sep=;',
				'Has test 10=Four'
			],
			[
				'Has test 9' => [ 'One,Two,Three' ],
				'Has test 10' => [ 'Four' ]
			]
		];

		// {{#...:
		// |Has test 11=Test 5-1|Test 5-2|Test 5-3|Test 5-4
		// |Has test 12=Test 5-5
		// |Has test 11=9,10,11,|+sep=
		// }}
		$provider[] = [
			[
				'Has test 11=Test 5-1',
				'Test 5-2',
				'Test 5-3',
				'Test 5-4',
				'Has test 12=Test 5-5',
				'Has test 11=9,10,11,',
				'+sep='
			],
			[
				'Has test 11' => [ 'Test 5-1', 'Test 5-2', 'Test 5-3', 'Test 5-4', '9', '10', '11' ],
				'Has test 12' => [ 'Test 5-5' ]
			]
		];

		// {{#...:
		// |Has test url=http://www.semantic-mediawiki.org/w/index.php?title=Subobject;http://www.semantic-mediawiki.org/w/index.php?title=Set|+sep=;
		// }}
		$provider[] = [
			[
				'Has test url=http://www.semantic-mediawiki.org/w/index.php?title=Subobject;http://www.semantic-mediawiki.org/w/index.php?title=Set',
				'+sep=;'
			],
			[
				'Has test url' => [ 'http://www.semantic-mediawiki.org/w/index.php?title=Subobject', 'http://www.semantic-mediawiki.org/w/index.php?title=Set' ]
			]
		];

		// {{#...:
		// |Foo=123|345|456|+pipe
		// }}
		$provider[] = [
			[
				'Foo=123',
				'345',
				'456',
				'+pipe'
			],
			[
				'Foo' => [ '123|345|456' ]
			]
		];

		// {{#...:
		// |@json={ "Foo": 123}
		// }}
		$provider[] = [
			[
				'@json={ "Foo": 123}'
			],
			[
				'Foo' => [ '123' ]
			]
		];

		// {{#...:
		// |@json={ "Foo": [123, 456] }
		// }}
		$provider[] = [
			[
				'@json={ "Foo": [123, 456] }'
			],
			[
				'Foo' => [ '123', '456' ]
			]
		];

		// Error
		// {{#...:
		// |@json={ "Foo": [123, 456] }
		// }}
		$provider[] = [
			[
				'@json={ Foo: [123, 456] }'
			],
			[
				'@json' => [ '{ Foo: [123, 456] }' ]
			]
		];

		// Avoid spaces on individual values
		// {{#...:
		// |Has test=One; Two|+sep=;
		// }}
		$provider[] = [
			[
				'Has test=One; Two',
				'+sep=;'
			],
			[
				'Has test' => [ 'One', 'Two' ]
			]
		];

		return $provider;
	}

	public function firstParameterDataProvider() {

		// {{#subobject:
		// |Has test 1=One
		// }}
		$provider[] = [
			[ '', 'Has test 1=One'],
			[ 'identifier' => null ]
		];

		// {{#set_recurring_event:Foo
		// |Has test 2=Two
		// |Has test 2=Three;Four|+sep=;
		// }}
		$provider[] = [
			[ 'Foo' , 'Has test 2=Two', 'Has test 2=Three;Four', '+sep=;' ],
			[ 'identifier' => 'Foo' ]
		];

		// {{#subobject:-
		// |Has test 2=Two
		// |Has test 2=Three;Four|+sep=;
		// }}
		$provider[] = [
			[ '-', 'Has test 2=Two', 'Has test 2=Three;Four', '+sep=;' ],
			[ 'identifier' => '-' ]
		];

		return $provider;
	}

}
