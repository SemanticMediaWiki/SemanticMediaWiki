<?php

namespace SMW\Tests\Schema\Filters;

use SMW\Schema\Filters\CategoryFilter;
use SMW\Schema\Compartment;
use SMW\Schema\CompartmentIterator;

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
			true
		];

		yield 'oneOf.2: single one_of, underscore condition' => [
			[ 'Foo bar', 'Bar' ],
			[
				'if' => [
					'category' => 'Foo_bar'
				]
			],
			true
		];

		yield 'oneOf.3: single one_of, underscore validation value' => [
			[ 'Foo_bar', 'Bar' ],
			[
				'if' => [
					'category' => 'Foo bar'
				]
			],
			true
		];

		yield 'oneOf.4: empty categories' => [
			[],
			[
				'if' => [
					'category' => 'Foo'
				]
			],
			false
		];

		yield 'oneOf.5: single no_match' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => 'no_match'
				]
			],
			false
		];

		yield 'oneOf.6: one_of matches' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Foobar' ] ]
				]
			],
			true
		];

		yield 'oneOf.7: one_of fails because more than one matches' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			false
		];

		yield 'oneOf.8: one_of fails because both match (onyl one is allowed to match)' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Bar', 'Foobar' ] ]
				]
			],
			false
		];

		// anyOf

		yield 'anyOf.1: anyOf does match because one or more match' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'anyOf' => [ 'Foo', 'Bar', 'Foobar' ] ]
				]
			],
			true
		];

		// allOf

		yield 'allOf.1: all_of matched' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			true
		];

		yield 'allOf.2: all_of failed' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'Foo', 'Foobar' ] ]
				]
			],
			false
		];

		// not

		yield 'not.1: not single, matches `not` condition' => [
			[ 'Foo' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo1', 'Foo2' ] ]
				]
			],
			true
		];

		yield 'not.2: not multiple, matches `not` condition' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo1', 'Foo2' ] ]
				]
			],
			true
		];

		yield 'not.3: not single, matches `not` condition' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo1' ] ]
				]
			],
			true
		];

		yield 'not.4: not multiple, fails because `not` matches one' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo1', 'Bar' ] ]
				]
			],
			false
		];

		yield 'not.5: not multiple, fails because `not` matches both' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => [ 'Foo', 'Bar' ] ]
				]
			],
			false
		];

		yield 'not.6: not single, fails because `not` matches one' => [
			[ 'Foo', 'Bar' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foo' ]
				]
			],
			false
		];

		// combined

		yield 'not.oneOf.1: not, oneOf combined, false because `not` is matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			false
		];

		yield 'not.oneOf.2: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo_1', 'Bar_2' ] ]
				]
			],
			false
		];

		yield 'not.oneOf.3: not, oneOf combined, false because `oneOf` does not match' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo_1', 'Bar_2' ] ]
				]
			],
			false
		];

		yield 'not.oneOf.4: not, oneOf combined, truthy because `oneOf` matches one and is not `Foobar`' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'oneOf' => [ 'Foo', 'Bar_2' ] ]
				]
			],
			true
		];

		yield 'not.oneOf.5: not, oneOf combined, truthy because `oneOf` matches one and is not `Foobar`' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'oneOf' => [ 'Foo', 'Bar_2' ], 'not' => 'Foobar' ]
				]
			],
			true
		];

		yield 'not.allOf.1: not, allOf combined, false because `allOf` fails' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar', 'allOf' => [ 'Foo', 'Bar_2' ] ]
				]
			],
			false
		];

		yield 'not.allOf.2: not, allOf combined, false because `allOf` fails' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'Foo', 'Bar_2' ], 'not' => 'Foobar' ]
				]
			],
			false
		];

		yield 'not.allOf.3: not, allOf combined, true' => [
			[ 'Foo', 'Bar', 'Foobar_1' ],
			[
				'if' => [
					'category' => [ 'allOf' => [ 'Foo', 'Bar', 'Foobar_1' ], 'not' => 'Foobar' ]
				]
			],
			true
		];

		yield 'not.anyOf.1: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'not' => 'Foobar_1', 'anyOf' => [ 'Foo', 'Bar' ] ]
				]
			],
			true
		];

		yield 'not.anyOf.2: not, oneOf combined, truthy because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'anyOf' => [ 'Foo', 'Bar' ], 'not' => 'Foobar_1' ]
				]
			],
			true
		];

		yield 'not.anyOf.3: not, oneOf combined, fails because `not` is not matched' => [
			[ 'Foo', 'Bar', 'Foobar' ],
			[
				'if' => [
					'category' => [ 'anyOf' => [ 'Foo', 'Bar' ], 'not' => 'Foobar' ]
				]
			],
			false
		];
	}

}
