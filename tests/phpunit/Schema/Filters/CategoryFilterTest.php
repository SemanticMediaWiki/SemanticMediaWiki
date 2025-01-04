<?php

namespace SMW\Tests\Schema\Filters;

use SMW\Schema\Filters\CategoryFilter;
use SMW\Schema\Compartment;
use SMW\Schema\Rule;
use SMW\Schema\CompartmentIterator;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Schema\Filters\CategoryFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CategoryFilterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CategoryFilter::class,
			new CategoryFilter()
		);
	}

	public function testGetName() {
		$instance = new CategoryFilter();

		$this->assertEquals(
			'category',
			$instance->getName()
		);
	}

	public function testIfCondition() {
		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	'if.category' );

		$instance = new CategoryFilter();
		$instance->filter( $compartment );
	}

	public function testNoCondition_FilterNotRequired() {
		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	'if.category' );

		$instance = new CategoryFilter();
		$instance->addOption( CategoryFilter::FILTER_CONDITION_NOT_REQUIRED, true );

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

		$instance = new CategoryFilter( $callback );

		$this->expectException( '\RuntimeException' );
		$instance->filter( $compartment );
	}

	/**
	 * @dataProvider categoryFilterProvider
	 */
	public function testHasMatches_Compartment( $categories, $compartment, $expected ) {
		$instance = new CategoryFilter(
			$categories
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
	 * @dataProvider categoryFilterProvider
	 */
	public function testHasMatches_Callback_Compartment( $categories, $compartment, $expected ) {
		$callback = function () use ( $categories ) {
			return $categories;
		};

		$instance = new CategoryFilter(
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
	 * @dataProvider categoryFilterProvider
	 */
	public function testHasMatches_Rule( $categories, $compartment, $expected, $score ) {
		$instance = new CategoryFilter(
			$categories
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
	 * @dataProvider categoryFilterProvider
	 */
	public function testHasMatches_CompartmentIterator( $categories, $compartment, $expected ) {
		$instance = new CategoryFilter(
			$categories
		);

		$instance->filter(
			new CompartmentIterator( [ $compartment ] )
		);

		$this->assertEquals(
			$expected,
			$instance->hasMatches()
		);
	}

	public function categoryFilterProvider() {
		yield 'oneOf.1: single one_of' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => 'NotFoo'
				]
			],
			true,
			1
		];

		yield 'oneOf.1: single one_of, flipped' => [
			[ 'NotFoo' => true, 'Bar' => true ],
			[
				'if' => [
					'category' => 'NotFoo'
				]
			],
			true,
			1
		];

		yield 'oneOf.2: single one_of, underscore condition' => [
			[ 'NotFoo bar', 'Bar' ],
			[
				'if' => [
					'category' => 'NotFoo_bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.3: single one_of, underscore validation value' => [
			[ 'NotFoo_bar', 'Bar' ],
			[
				'if' => [
					'category' => 'NotFoo bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.4: empty categories' => [
			[],
			[
				'if' => [
					'category' => 'NotFoo'
				]
			],
			false,
			0
		];

		yield 'oneOf.5: single no_match' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => 'no_match'
				]
			],
			false,
			0
		];

		yield 'oneOf.6: one_of matches' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'NotFoo', 'NotFoobar' ] ]
				]
			],
			true,
			1
		];

		yield 'oneOf.7: one_of fails because more than one matches' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'oneOf.8: one_of fails because both match (onyl one is allowed to match)' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'NotFoo', 'Bar', 'NotFoobar' ] ]
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
					'category' => [ 'anyOf' => [ 'NotFoo', 'Bar', 'NotFoobar' ] ]
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
					'category' => [ 'allOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			true,
			1
		];

		yield 'allOf.2: all_of failed' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'NotFoo', 'NotFoobar' ] ]
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
					'category' => [ 'not' => [ 'NotFoo1', 'NotFoo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.2: not multiple, matches `not` condition' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'NotFoo1', 'NotFoo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.3: not single, matches `not` condition' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'NotFoo1' ] ]
				]
			],
			true,
			1
		];

		yield 'not.4: not multiple, fails because `not` matches one' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'NotFoo1', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.5: not multiple, fails because `not` matches both' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.6: not single, fails because `not` matches one' => [
			[ 'NotFoo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => 'NotFoo' ]
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
					'category' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.2: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'category' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.3: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.4: not, oneOf combined, truthy because `oneOf` matches one and is not `NotFoobar`' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'NotFoobar', 'oneOf' => [ 'NotFoo', 'Bar_2' ] ]
				]
			],
			true,
			2
		];

		yield 'not.oneOf.5: not, oneOf combined, truthy because `oneOf` matches one and is not `NotFoobar`' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'NotFoo', 'Bar_2' ], 'not' => 'NotFoobar' ]
				]
			],
			true,
			2
		];

		yield 'not.allOf.1: not, allOf combined, false because `allOf` fails' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'NotFoobar', 'allOf' => [ 'NotFoo', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.2: not, allOf combined, false because `allOf` fails' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'NotFoo', 'Bar_2' ], 'not' => 'NotFoobar' ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.3: not, allOf combined, true' => [
			[ 'NotFoo', 'Bar', 'NotFoobar_1' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'NotFoo', 'Bar', 'NotFoobar_1' ], 'not' => 'NotFoobar' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.1: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'category' => [ 'not' => 'NotFoobar_1', 'anyOf' => [ 'NotFoo', 'Bar' ] ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.2: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'category' => [ 'anyOf' => [ 'NotFoo', 'Bar' ], 'not' => 'NotFoobar_1' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.3: not, oneOf combined, fails because `not` is not matched' => [
			[ 'NotFoo', 'Bar', 'NotFoobar' ],
			[
				'if' => [
					'category' => [ 'anyOf' => [ 'NotFoo', 'Bar' ], 'not' => 'NotFoobar' ]
				]
			],
			false,
			1
		];
	}

}
