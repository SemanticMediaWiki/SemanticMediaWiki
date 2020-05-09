<?php

namespace SMW\Tests\Schema\Filters;

use SMW\Schema\Filters\PropertyFilter;
use SMW\Schema\Compartment;
use SMW\Schema\Rule;
use SMW\Schema\CompartmentIterator;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Schema\Filters\PropertyFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyFilterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PropertyFilter::class,
			new PropertyFilter()
		);
	}

	public function testGetName() {

		$instance = new PropertyFilter();

		$this->assertEquals(
			'property',
			$instance->getName()
		);
	}

	public function testIfCondition() {

		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'if.property' ) );

		$instance = new PropertyFilter();
		$instance->filter( $compartment );
	}

	public function testNoCondition_FilterNotRequired() {

		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'if.property' ) );

		$instance = new PropertyFilter();
		$instance->addOption( PropertyFilter::FILTER_CONDITION_NOT_REQUIRED, true );

		$instance->filter( $compartment );

		$this->assertEquals(
			[ $compartment ],
			$instance->getMatches()
		);
	}

	public function testFilterOnCallbackWhileFailingReturnFormat_ThrowsException() {

		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function() {
			return null;
		};

		$instance = new PropertyFilter( $callback );

		$this->expectException( '\RuntimeException' );
		$instance->filter( $compartment );
	}

	/**
	 * @dataProvider propertyFilterProvider
	 */
	public function testHasMatches_Compartment( $properties, $compartment, $expected ) {

		$instance = new PropertyFilter(
			$properties
		);

		$instance->filter(
			new Compartment( $compartment )
		);

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);
	}

	/**
	 * @dataProvider propertyFilterProvider
	 */
	public function testHasMatches_Callback_Compartment( $properties, $compartment, $expected ) {

		$callback = function() use ( $properties ) {
			return $properties;
		};

		$instance = new PropertyFilter(
			$callback
		);

		$instance->filter(
			new Compartment( $compartment )
		);

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);
	}

	/**
	 * @dataProvider propertyFilterProvider
	 */
	public function testHasMatches_Rule( $properties, $compartment, $expected, $score ) {

		$instance = new PropertyFilter(
			$properties
		);

		$rule = new Rule(
			$compartment
		);

		$instance->filter( $rule );

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);

		$this->assertEquals(
			$score,
			$rule->filterScore
		);
	}

	/**
	 * @dataProvider propertyFilterProvider
	 */
	public function testHasMatches_CompartmentIterator( $properties, $compartment, $expected ) {

		$instance = new PropertyFilter(
			$properties
		);

		$instance->filter(
			new CompartmentIterator( [ $compartment ] )
		);

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);
	}

	public function propertyFilterProvider() {

		yield 'oneOf.1: single one_of' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => 'Foo'
				]
			],
			true,
			1
		];

		yield 'oneOf.2: single one_of, underscore condition' => [
			[ 'Foo bar', 'Bar' ],
			[
				'if' => [
					'property' => 'Foo_bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.3: single one_of, underscore validation value' => [
			[ 'Foo_bar', 'Bar' ],
			[
				'if' => [
					'property' => 'Foo bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.4: empty properties' => [
			[],
			[
				'if' => [
					'property' => 'Foo'
				]
			],
			false,
			0
		];

		yield 'oneOf.5: single no_match' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => 'no_match'
				]
			],
			false,
			0
		];

		yield 'oneOf.6: one_of matches' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'Foo', 'Foobar' ] ]
				]
			],
			true,
			1
		];

		yield 'oneOf.7: one_of fails because more than one matches' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'oneOf.8: one_of fails because both match (only one is allowed to match)' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'Foo', 'Bar', 'Foobar' ] ]
				]
			],
			false,
			0
		];

		// anyOf

		yield 'anyOf.1: anyOf does match because one or more match' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'anyOf' => [ 'Foo', 'Bar', 'Foobar' ] ]
				]
			],
			true,
			1
		];

		// allOf

		yield 'allOf.1: all_of matched' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			true,
			1
		];

		yield 'allOf.2: all_of failed' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'Foo', 'Foobar' ] ]
				]
			],
			false,
			0
		];

		// not

		yield 'not.1: not single, matches `not` condition' => [
			[ 'Foo' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'Foo1', 'Foo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.2: not multiple, matches `not` condition' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'Foo1', 'Foo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.3: not single, matches `not` condition' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'Foo1' ] ]
				]
			],
			true,
			1
		];

		yield 'not.4: not multiple, fails because `not` matches one' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'Foo1', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.5: not multiple, fails because `not` matches both' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'Foo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.6: not single, fails because `not` matches one' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => 'Foo' ]
				]
			],
			false,
			0
		];

		// combined

		yield 'not.oneOf.1: not, oneOf combined, false because `not` is matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'property' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.2: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'property' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.3: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'property' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.4: not, oneOf combined, truthy because `oneOf` matches one and is not `Foobar`' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'property' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo', 'Bar_2' ] ]
				]
			],
			true,
			2
		];

		yield 'not.oneOf.5: not, oneOf combined, truthy because `oneOf` matches one and is not `Foobar`' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'Foo', 'Bar_2' ], 'not' => 'Foobar' ]
				]
			],
			true,
			2
		];

		yield 'not.allOf.1: not, allOf combined, false because `allOf` fails' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'property' => [ 'not' => 'Foobar', 'allOf' => [ 'Foo', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.2: not, allOf combined, false because `allOf` fails' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'Foo', 'Bar_2' ], 'not' => 'Foobar' ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.3: not, allOf combined, true' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'Foo', 'Bar', 'Foobar_1' ], 'not' => 'Foobar' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.1: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'property' => [ 'not' => 'Foobar_1', 'anyOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.2: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'property' => [ 'anyOf' => [ 'Foo', 'Bar' ], 'not' => 'Foobar_1' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.3: not, oneOf combined, fails because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'property' => [ 'anyOf' => [ 'Foo', 'Bar' ], 'not' => 'Foobar' ]
				]
			],
			false,
			1
		];
	}

}
