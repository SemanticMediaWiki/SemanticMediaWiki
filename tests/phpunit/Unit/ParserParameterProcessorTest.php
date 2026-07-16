<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\ParserParameterProcessor;

/**
 * @covers \SMW\ParserParameterProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParserParameterProcessorTest extends TestCase {

	/**
	 * @dataProvider parametersDataProvider
	 */
	public function testCanConstruct( array $parameters ) {
		$this->assertInstanceOf(
			ParserParameterProcessor::class,
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
			$instance->getFirstParameter()
		);
	}

	public function testBareTokensContinuePrecedingProperty() {
		$instance = new ParserParameterProcessor( [ 'Foo=1', '2', '3' ] );

		$this->assertSame(
			[ 'Foo' => [ '1', '2', '3' ] ],
			$instance->toArray()
		);
	}

	public function testFirstBareTokenBecomesFirstParameterWithSpacesConvertedToUnderscores() {
		$instance = new ParserParameterProcessor( [ 'Bar baz', 'Foo=1' ] );

		$this->assertSame(
			'Bar_baz',
			$instance->getFirstParameter()
		);
	}

	public function testBareSepUsesDefaultCommaSeparator() {
		$instance = new ParserParameterProcessor( [ 'Foo=1,2', '+sep' ] );

		$this->assertSame(
			[ 'Foo' => [ '1', '2' ] ],
			$instance->toArray()
		);
	}

	public function testSepWithExplicitValueUsesThatSeparator() {
		$instance = new ParserParameterProcessor( [ 'Foo=1;2', '+sep=;' ] );

		$this->assertSame(
			[ 'Foo' => [ '1', '2' ] ],
			$instance->toArray()
		);
	}

	public function testSepWithEmptyValueFallsBackToDefaultComma() {
		$instance = new ParserParameterProcessor( [ 'Foo=1,2', '+sep=' ] );

		$this->assertSame(
			[ 'Foo' => [ '1', '2' ] ],
			$instance->toArray()
		);
	}

	public function testTrailingSepIsConsumedWithoutAffectingOtherAssignments() {
		$instance = new ParserParameterProcessor( [ 'Foo=1', 'Bar=2', '+sep' ] );

		$this->assertSame(
			[ 'Foo' => [ '1' ], 'Bar' => [ '2' ] ],
			$instance->toArray()
		);
	}

	public function testBarePipeTokenIsConsumed() {
		$instance = new ParserParameterProcessor( [ 'Foo=a', '+pipe' ] );

		$this->assertSame(
			[ 'Foo' => [ 'a' ] ],
			$instance->toArray()
		);
	}

	public function testPipeTokenWithWhitespaceIsNotConsumedAndBecomesValue() {
		// +pipe is compared against the raw, untrimmed look-ahead token, so a
		// padded ' +pipe ' does not match and falls through to the bare-token rule.
		$instance = new ParserParameterProcessor( [ 'Foo=a', ' +pipe ' ] );

		$this->assertSame(
			[ 'Foo' => [ 'a', '+pipe' ] ],
			$instance->toArray()
		);
	}

	public function testSepFollowedByPipeAreBothConsumedAndValuesPipeJoined() {
		$instance = new ParserParameterProcessor( [ 'Foo=1;2', '+sep=;', '+pipe' ] );

		$this->assertSame(
			[ 'Foo' => [ '1|2' ] ],
			$instance->toArray()
		);
	}

	public function testPipeFollowedBySepLeavesSepAsLiteralJunkKey() {
		// +sep is only ever inspected before +pipe, so once +pipe is consumed the
		// trailing +sep=; is not recognized and lands as its own literal key.
		$instance = new ParserParameterProcessor( [ 'Foo=a', '+pipe', '+sep=;' ] );

		$this->assertSame(
			[ 'Foo' => [ 'a' ], '+sep' => [ ';' ] ],
			$instance->toArray()
		);
	}

	public function testValidJsonObjectMergesKeysWrappingScalarsAndEmptyStrings() {
		$instance = new ParserParameterProcessor(
			[ '@json={"Has one":"1","Has empty":"","Has many":["a","b"]}' ]
		);

		$this->assertSame(
			[
				'Has one' => [ '1' ],
				'Has empty' => [],
				'Has many' => [ 'a', 'b' ],
			],
			$instance->toArray()
		);
	}

	public function testMalformedJsonIsKeptVerbatimAndReportsError() {
		$instance = new ParserParameterProcessor( [ '@json={ Foo }' ] );

		$this->assertSame(
			[ '@json' => [ '{ Foo }' ] ],
			$instance->toArray()
		);
		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testNonStringParameterIsSkipped() {
		$instance = new ParserParameterProcessor( [ 42, 'Foo=1' ] );

		$this->assertSame(
			[ 'Foo' => [ '1' ] ],
			$instance->toArray()
		);
	}

	public function testDisplayOptionWithValueBecomesOwnJunkKey() {
		// Default construction does not capture +display: with a value it is parsed
		// as an ordinary (junk) property assignment.
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display=link' ] );

		$this->assertSame(
			[ 'Foo' => [ 'x' ], '+display' => [ 'link' ] ],
			$instance->toArray()
		);
	}

	public function testBareDisplayTokenIsSwallowedAsValueOfPrecedingProperty() {
		// Default construction does not capture +display: a bare token continues
		// the preceding property just like any other bare token.
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display' ] );

		$this->assertSame(
			[ 'Foo' => [ 'x', '+display' ] ],
			$instance->toArray()
		);
	}

	public function testDisplayOptionsAreEmptyByDefault() {
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display' ] );

		$this->assertSame(
			[],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionIsCapturedForPrecedingPropertyWhenEnabled() {
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display=link' ], true );

		$this->assertSame(
			[ 'Foo' => [ 'x' ] ],
			$instance->toArray()
		);
		$this->assertSame(
			[ 'Foo' => 'link' ],
			$instance->getDisplayOptions()
		);
	}

	public function testBareDisplayOptionIsCapturedAsEmptyMode() {
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display' ], true );

		$this->assertSame(
			[ 'Foo' => [ 'x' ] ],
			$instance->toArray()
		);
		$this->assertSame(
			[ 'Foo' => '' ],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionWithEmptyValueIsCapturedAsEmptyMode() {
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display=' ], true );

		$this->assertSame(
			[ 'Foo' => '' ],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionModeValueIsTrimmed() {
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display= link ' ], true );

		$this->assertSame(
			[ 'Foo' => 'link' ],
			$instance->getDisplayOptions()
		);
	}

	public function testUnknownDisplayModeIsCapturedVerbatim() {
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display=foo' ], true );

		$this->assertSame(
			[ 'Foo' => 'foo' ],
			$instance->getDisplayOptions()
		);
	}

	public function testRepeatedDisplayOptionOnOneAssignmentLastOneWins() {
		$instance = new ParserParameterProcessor( [ 'Foo=x', '+display=link', '+display=text' ], true );

		$this->assertSame(
			[ 'Foo' => 'text' ],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionBeforeSepAppliesToSameAssignment() {
		$instance = new ParserParameterProcessor( [ 'Foo=1;2', '+display=link', '+sep=;' ], true );

		$this->assertSame(
			[ 'Foo' => [ '1', '2' ] ],
			$instance->toArray()
		);
		$this->assertSame(
			[ 'Foo' => 'link' ],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionAfterSepAppliesToSameAssignment() {
		$instance = new ParserParameterProcessor( [ 'Foo=1;2', '+sep=;', '+display=link' ], true );

		$this->assertSame(
			[ 'Foo' => [ '1', '2' ] ],
			$instance->toArray()
		);
		$this->assertSame(
			[ 'Foo' => 'link' ],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionAppliesOnlyToPrecedingAssignment() {
		$instance = new ParserParameterProcessor( [ 'Foo=a', 'Bar=b', '+display=link' ], true );

		$this->assertSame(
			[ 'Bar' => 'link' ],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionForMultiElementAssignmentAttachesToProperty() {
		$instance = new ParserParameterProcessor( [ 'Foo=1', '2', '+display=text' ], true );

		$this->assertSame(
			[ 'Foo' => [ '1', '2' ] ],
			$instance->toArray()
		);
		$this->assertSame(
			[ 'Foo' => 'text' ],
			$instance->getDisplayOptions()
		);
	}

	public function testDisplayOptionDoesNotUnlockSepAfterPipe() {
		// The legacy rule that +sep is never consumed after +pipe still applies
		// when a +display option sits between them.
		$instance = new ParserParameterProcessor( [ 'Foo=a', '+pipe', '+display', '+sep=;' ], true );

		$this->assertSame(
			[ 'Foo' => [ 'a' ], '+sep' => [ ';' ] ],
			$instance->toArray()
		);
		$this->assertSame(
			[ 'Foo' => '' ],
			$instance->getDisplayOptions()
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
				'Has test 6' => [ '1', '2', '3' ],
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
			[ '', 'Has test 1=One' ],
			[ 'identifier' => null ]
		];

		// {{#set_recurring_event:Foo
		// |Has test 2=Two
		// |Has test 2=Three;Four|+sep=;
		// }}
		$provider[] = [
			[ 'Foo', 'Has test 2=Two', 'Has test 2=Three;Four', '+sep=;' ],
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
