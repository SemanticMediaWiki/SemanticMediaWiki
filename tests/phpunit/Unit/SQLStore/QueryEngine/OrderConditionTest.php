<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\OrderCondition;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\OrderCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OrderConditionTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $querySegmentListBuilder;

	protected function setUp() {

		$this->querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			OrderCondition::class,
			new OrderCondition( $this->querySegmentListBuilder )
		);
	}

	public function testApplyWithoutSortKey() {

		$this->querySegmentListBuilder->expects( $this->once() )
			->method( 'getQuerySegmentList' );

		$instance = new OrderCondition(
			$this->querySegmentListBuilder
		);

		$instance->apply( 42 );
	}

	/**
	 * @dataProvider sortKeyProvider
	 */
	public function testApplyWithSortKey( $sortKeys ) {

		$querySegment = new QuerySegment();

		$this->querySegmentListBuilder->expects( $this->once() )
			->method( 'getQuerySegmentList' );

		$this->querySegmentListBuilder->expects( $this->atLeastOnce() )
			->method( 'findQuerySegment' )
			->will( $this->returnValue( $querySegment ) );

		$instance = new OrderCondition(
			$this->querySegmentListBuilder
		);

		$instance->setSortKeys( $sortKeys );

		$instance->apply( 42 );
		$querySegment->reset();
	}

	public function testApplyWithInvalidSortKeyThrowsException() {

		$querySegment = new QuerySegment();

		$this->querySegmentListBuilder->expects( $this->never() )
			->method( 'getQuerySegmentList' );

		$this->querySegmentListBuilder->expects( $this->atLeastOnce() )
			->method( 'findQuerySegment' )
			->will( $this->returnValue( $querySegment ) );

		$instance = new OrderCondition(
			$this->querySegmentListBuilder
		);

		$instance->setSortKeys( [
				42 => 'ASC'
			]
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->apply( 42 );

		$querySegment->reset();
	}

	public function sortKeyProvider() {

		$provider[] = [
			[
				'' => 'ASC'
			]
		];

		$provider[] = [
			[
				'#' => 'DESC'
			]
		];

		$provider[] = [
			[
				'Foo' => 'ASC'
			]
		];

		$provider[] = [
			[
				'Foo.bar' => 'ASC'
			]
		];

		return $provider;
	}

}
