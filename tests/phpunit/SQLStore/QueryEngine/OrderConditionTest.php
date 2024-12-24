<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\OrderCondition;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\OrderCondition
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OrderConditionTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $conditionBuilder;

	protected function setUp(): void {
		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OrderCondition::class,
			new OrderCondition( $this->conditionBuilder )
		);
	}

	public function testApplyWithoutSortKey() {
		$this->conditionBuilder->expects( $this->once() )
			->method( 'getQuerySegmentList' );

		$instance = new OrderCondition();
		$instance->addConditions( $this->conditionBuilder, 42 );
	}

	public function testApplyWithInvalidSortKeyThrowsException() {
		$querySegment = new QuerySegment();

		$this->conditionBuilder->expects( $this->never() )
			->method( 'getQuerySegmentList' );

		$this->conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'findQuerySegment' )
			->willReturn( $querySegment );

		$instance = new OrderCondition();

		$instance->setSortKeys( [
				42 => 'ASC'
			]
		);

		$this->expectException( 'RuntimeException' );
		$instance->addConditions( $this->conditionBuilder, 42 );

		$querySegment->reset();
	}

	/**
	 * @dataProvider sortKeyProvider
	 */
	public function testApplyWithSortKey( $sortKeys ) {
		$querySegment = new QuerySegment();

		$this->conditionBuilder->expects( $this->once() )
			->method( 'getQuerySegmentList' );

		$this->conditionBuilder->expects( $this->atLeastOnce() )
			->method( 'findQuerySegment' )
			->willReturn( $querySegment );

		$instance = new OrderCondition();

		$instance->setSortKeys( $sortKeys );

		$instance->addConditions( $this->conditionBuilder, 42 );
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
