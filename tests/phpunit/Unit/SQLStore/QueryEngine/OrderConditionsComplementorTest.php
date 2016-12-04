<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\OrderConditionsComplementor;
use SMW\SQLStore\QueryEngine\QuerySegment;

/**
 * @covers \SMW\SQLStore\QueryEngine\OrderConditionsComplementor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OrderConditionsComplementorTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentListBuilder;

	protected function setUp() {

		$this->querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\OrderConditionsComplementor',
			new OrderConditionsComplementor( $this->querySegmentListBuilder )
		);
	}

	public function testApplyOrderConditionsWithoutSortKey() {

		$this->querySegmentListBuilder->expects( $this->once() )
			->method( 'getQuerySegmentList' );

		$instance = new OrderConditionsComplementor(
			$this->querySegmentListBuilder
		);

		$instance->applyOrderConditions( 42 );
	}

	/**
	 * @dataProvider sortKeyProvider
	 */
	public function testApplyOrderConditionsWithSortKey( $sortKeys ) {

		$querySegment = new QuerySegment();

		$this->querySegmentListBuilder->expects( $this->once() )
			->method( 'getQuerySegmentList' );

		$this->querySegmentListBuilder->expects( $this->atLeastOnce() )
			->method( 'findQuerySegment' )
			->will( $this->returnValue( $querySegment ) );

		$instance = new OrderConditionsComplementor(
			$this->querySegmentListBuilder
		);

		$instance->setSortKeys( $sortKeys );

		$instance->applyOrderConditions( 42 );
		$querySegment->reset();
	}

	public function testApplyOrderConditionsWithInvalidSortKeyThrowsException() {

		$querySegment = new QuerySegment();

		$this->querySegmentListBuilder->expects( $this->never() )
			->method( 'getQuerySegmentList' );

		$this->querySegmentListBuilder->expects( $this->atLeastOnce() )
			->method( 'findQuerySegment' )
			->will( $this->returnValue( $querySegment ) );

		$instance = new OrderConditionsComplementor(
			$this->querySegmentListBuilder
		);

		$instance->setSortKeys( array(
				42 => 'ASC'
			)
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->applyOrderConditions( 42 );

		$querySegment->reset();
	}

	public function sortKeyProvider() {

		$provider[] = array(
			array(
				'' => 'ASC'
			)
		);

		$provider[] = array(
			array(
				'#' => 'DESC'
			)
		);

		$provider[] = array(
			array(
				'Foo' => 'ASC'
			)
		);

		$provider[] = array(
			array(
				'Foo.bar' => 'ASC'
			)
		);

		return $provider;
	}

}
