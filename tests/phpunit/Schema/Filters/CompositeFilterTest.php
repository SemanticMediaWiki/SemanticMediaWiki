<?php

namespace SMW\Tests\Schema\Filters;

use SMW\Schema\Filters\CompositeFilter;
use SMW\Schema\Compartment;
use SMW\Schema\Rule;
use SMW\Schema\CompartmentIterator;

/**
 * @covers \SMW\Schema\Filters\CompositeFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CompositeFilterTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CompositeFilter::class,
			new CompositeFilter( [] )
		);
	}

	public function testFilter() {
		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$filter_1 = $this->getMockBuilder( '\SMW\Schema\SchemaFilter' )
			->disableOriginalConstructor()
			->getMock();

		$filter_2 = $this->getMockBuilder( '\SMW\Schema\ChainableFilter' )
			->disableOriginalConstructor()
			->getMock();

		$filter_2->expects( $this->once() )
			->method( 'filter' )
			->with(	$compartment );

		$filter_2->expects( $this->once() )
			->method( 'getMatches' )
			->willReturn( [ 'Foo' ] );

		$instance = new CompositeFilter( [ $filter_1, $filter_2 ] );
		$instance->addOption( 'foo', true );

		$instance->filter( $compartment );

		$this->assertTrue(
			$instance->hasMatches()
		);

		$this->assertEquals(
			[ 'Foo' ],
			$instance->getMatches()
		);
	}

	public function testSortMatches_Empty() {
		$instance = new CompositeFilter( [] );
		$instance->sortMatches( 'foo' );

		$this->assertFalse(
			$instance->hasMatches()
		);
	}

	public function testSortMatches() {
		$rule_1 = $this->getMockBuilder( '\SMW\Schema\Rule' )
			->disableOriginalConstructor()
			->onlyMethods( [] )
			->getMock();

		$rule_1->incrFilterScore();
		$rule_1->incrFilterScore();

		$rule_2 = $this->getMockBuilder( '\SMW\Schema\Rule' )
			->disableOriginalConstructor()
			->getMock();

		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$filter_1 = $this->getMockBuilder( '\SMW\Schema\SchemaFilter' )
			->disableOriginalConstructor()
			->getMock();

		$filter_2 = $this->getMockBuilder( '\SMW\Schema\ChainableFilter' )
			->disableOriginalConstructor()
			->getMock();

		$filter_2->expects( $this->once() )
			->method( 'filter' )
			->with(	$compartment );

		$filter_2->expects( $this->once() )
			->method( 'getMatches' )
			->willReturn( [ $rule_2, $rule_1 ] );

		$instance = new CompositeFilter( [ $filter_1, $filter_2 ] );
		$instance->filter( $compartment );

		$instance->sortMatches( CompositeFilter::SORT_FILTER_SCORE, 'desc' );

		$this->assertEquals(
			[ $rule_1, $rule_2 ],
			$instance->getMatches()
		);

		$instance->sortMatches( CompositeFilter::SORT_FILTER_SCORE, 'asc' );

		$this->assertEquals(
			[ $rule_2, $rule_1 ],
			$instance->getMatches()
		);
	}

}
