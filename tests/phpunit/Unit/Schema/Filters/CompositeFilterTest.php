<?php

namespace SMW\Tests\Unit\Schema\Filters;

use PHPUnit\Framework\TestCase;
use SMW\Schema\ChainableFilter;
use SMW\Schema\Compartment;
use SMW\Schema\Filters\CompositeFilter;
use SMW\Schema\Rule;
use SMW\Schema\SchemaFilter;

/**
 * @covers \SMW\Schema\Filters\CompositeFilter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class CompositeFilterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CompositeFilter::class,
			new CompositeFilter( [] )
		);
	}

	public function testFilter() {
		$compartment = $this->getMockBuilder( Compartment::class )
			->disableOriginalConstructor()
			->getMock();

		$filter_1 = $this->getMockBuilder( SchemaFilter::class )
			->disableOriginalConstructor()
			->getMock();

		$filter_2 = $this->getMockBuilder( ChainableFilter::class )
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
		$rule_1 = $this->getMockBuilder( Rule::class )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$rule_1->incrFilterScore();
		$rule_1->incrFilterScore();

		$rule_2 = $this->getMockBuilder( Rule::class )
			->disableOriginalConstructor()
			->getMock();

		$compartment = $this->getMockBuilder( Compartment::class )
			->disableOriginalConstructor()
			->getMock();

		$filter_1 = $this->getMockBuilder( SchemaFilter::class )
			->disableOriginalConstructor()
			->getMock();

		$filter_2 = $this->getMockBuilder( ChainableFilter::class )
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
