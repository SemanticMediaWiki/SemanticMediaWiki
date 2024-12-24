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
class PropertyFilterTest extends \PHPUnit\Framework\TestCase {

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
			->with(	'if.property' );

		$instance = new PropertyFilter();
		$instance->filter( $compartment );
	}

	public function testNoCondition_FilterNotRequired() {
		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	'if.property' );

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

		$callback = function () {
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
		$callback = function () use ( $properties ) {
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
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => 'NotFoo'
				]
			],
			true,
			1
		];

		yield 'oneOf.2: single one_of, underscore condition' => [
			[ 'NotFoo bar', 'Bar' ],
			[
				'if' => [
					'property' => 'NotFoo_bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.3: single one_of, underscore validation value' => [
			[ 'NotFoo_bar', 'Bar' ],
			[
				'if' => [
					'property' => 'NotFoo bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.4: empty properties' => [
			[],
			[
				'if' => [
					'property' => 'NotFoo'
				]
			],
			false,
			0
		];

		yield 'oneOf.5: single no_match' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => 'no_match'
				]
			],
			false,
			0
		];

		yield 'oneOf.6: one_of matches' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'NotFoo', 'NotFoobar' ] ]
				]
			],
			true,
			1
		];

		yield 'oneOf.7: one_of fails because more than one matches' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'oneOf.8: one_of fails because both match (only one is allowed to match)' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'NotFoo', 'Bar', 'NotFoobar' ] ]
				]
			],
			false,
			0
		];

		// anyOf

		yield 'anyOf.1: anyOf does match because one or more match' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'anyOf' => [ 'NotFoo', 'Bar', 'NotFoobar' ] ]
				]
			],
			true,
			1
		];

		// allOf

		yield 'allOf.1: all_of matched' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			true,
			1
		];

		yield 'allOf.2: all_of failed' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'NotFoo', 'NotFoobar' ] ]
				]
			],
			false,
			0
		];

		// not

		yield 'not.1: not single, matches `not` condition' => [
			[ 'NotFoo' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'NotFoo1', 'NotFoo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.2: not multiple, matches `not` condition' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'NotFoo1', 'NotFoo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.3: not single, matches `not` condition' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'NotFoo1' ] ]
				]
			],
			true,
			1
		];

		yield 'not.4: not multiple, fails because `not` matches one' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'NotFoo1', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.5: not multiple, fails because `not` matches both' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.6: not single, fails because `not` matches one' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'property' => [ 'not' => 'NotFoo' ]
				]
			],
			false,
			0
		];

		// combined

		yield 'not.oneOf.1: not, oneOf combined, false because `not` is matched' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'property' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.2: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'property' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.3: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'property' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.4: not, oneOf combined, truthy because `oneOf` matches one and is not `NotFoobar`' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'property' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo', 'Bar_2' ] ]
				]
			],
			true,
			2
		];

		yield 'not.oneOf.5: not, oneOf combined, truthy because `oneOf` matches one and is not `NotFoobar`' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'property' => [ 'oneOf' => [ 'NotFoo', 'Bar_2' ], 'not' => 'NotFoobar' ]
				]
			],
			true,
			2
		];

		yield 'not.allOf.1: not, allOf combined, false because `allOf` fails' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'property' => [ 'not' => 'NotFoobar', 'allOf' => [ 'NotFoo', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.2: not, allOf combined, false because `allOf` fails' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'NotFoo', 'Bar_2' ], 'not' => 'NotFoobar' ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.3: not, allOf combined, true' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'property' => [ 'allOf' => [ 'NotFoo', 'Bar', 'NotFoobar_1' ], 'not' => 'NotFoobar' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.1: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'property' => [ 'not' => 'NotFoobar_1', 'anyOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.2: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'property' => [ 'anyOf' => [ 'NotFoo', 'Bar' ], 'not' => 'NotFoobar_1' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.3: not, oneOf combined, fails because `not` is not matched' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'property' => [ 'anyOf' => [ 'NotFoo', 'Bar' ], 'not' => 'NotFoobar' ]
				]
			],
			false,
			1
		];
	}

}
