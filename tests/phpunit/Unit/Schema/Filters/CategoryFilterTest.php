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
class CategoryFilterTest extends \PHPUnit_Framework_TestCase {

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
			->with(	$this->equalTo( 'if.category' ) );

		$instance = new CategoryFilter();
		$instance->filter( $compartment );
	}

	public function testNoCondition_FilterNotRequired() {

		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$compartment->expects( $this->once() )
			->method( 'get' )
			->with(	$this->equalTo( 'if.category' ) );

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

		$callback = function() {
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

		$callback = function() use ( $categories ) {
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
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => 'Foo'
				]
			],
			true,
			1
		];

		yield 'oneOf.1: single one_of, flipped' => [
			[ 'Foo' => true, 'Bar' => true ],
			[
				'if' => [
					'category' => 'Foo'
				]
			],
			true,
			1
		];

		yield 'oneOf.2: single one_of, underscore condition' => [
			[ 'Foo bar', 'Bar' ],
			[
				'if' => [
					'category' => 'Foo_bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.3: single one_of, underscore validation value' => [
			[ 'Foo_bar', 'Bar' ],
			[
				'if' => [
					'category' => 'Foo bar'
				]
			],
			true,
			1
		];

		yield 'oneOf.4: empty categories' => [
			[],
			[
				'if' => [
					'category' => 'Foo'
				]
			],
			false,
			0
		];

		yield 'oneOf.5: single no_match' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => 'no_match'
				]
			],
			false,
			0
		];

		yield 'oneOf.6: one_of matches' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Foobar' ] ]
				]
			],
			true,
			1
		];

		yield 'oneOf.7: one_of fails because more than one matches' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'oneOf.8: one_of fails because both match (onyl one is allowed to match)' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Bar', 'Foobar' ] ]
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
					'category' => [ 'anyOf' => [ 'Foo', 'Bar', 'Foobar' ] ]
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
					'category' => [ 'allOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			true,
			1
		];

		yield 'allOf.2: all_of failed' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'Foo', 'Foobar' ] ]
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
					'category' => [ 'not' => [ 'Foo1', 'Foo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.2: not multiple, matches `not` condition' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo1', 'Foo2' ] ]
				]
			],
			true,
			1
		];

		yield 'not.3: not single, matches `not` condition' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo1' ] ]
				]
			],
			true,
			1
		];

		yield 'not.4: not multiple, fails because `not` matches one' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo1', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.5: not multiple, fails because `not` matches both' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.6: not single, fails because `not` matches one' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foo' ]
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
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.2: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.3: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo_1', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.oneOf.4: not, oneOf combined, truthy because `oneOf` matches one and is not `Foobar`' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo', 'Bar_2' ] ]
				]
			],
			true,
			2
		];

		yield 'not.oneOf.5: not, oneOf combined, truthy because `oneOf` matches one and is not `Foobar`' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Bar_2' ], 'not' => 'Foobar' ]
				]
			],
			true,
			2
		];

		yield 'not.allOf.1: not, allOf combined, false because `allOf` fails' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'allOf' => [ 'Foo', 'Bar_2' ] ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.2: not, allOf combined, false because `allOf` fails' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'Foo', 'Bar_2' ], 'not' => 'Foobar' ]
				]
			],
			false,
			0
		];

		yield 'not.allOf.3: not, allOf combined, true' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'Foo', 'Bar', 'Foobar_1' ], 'not' => 'Foobar' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.1: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar_1', 'anyOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.2: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'anyOf' => [ 'Foo', 'Bar' ], 'not' => 'Foobar_1' ]
				]
			],
			true,
			2
		];

		yield 'not.anyOf.3: not, oneOf combined, fails because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'anyOf' => [ 'Foo', 'Bar' ], 'not' => 'Foobar' ]
				]
			],
			false,
			1
		];
	}

}
