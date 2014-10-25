<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\ConditionBuilderStrategyFinder;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\ConditionBuilderStrategyFinder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConditionBuilderStrategyFinderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilderStrategyFinder',
			new ConditionBuilderStrategyFinder( $compoundConditionBuilder )
		);
	}

	public function testRegisterStrategyForDescription() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder->expects( $this->once() )
			->method( 'canBuildConditionFor' )
			->will( $this->returnValue( true ) );

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConditionBuilderStrategyFinder( $compoundConditionBuilder );
		$instance->clear();

		$instance->registerConditionBuilder( $conditionBuilder );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilder\ConditionBuilder',
			$instance->findStrategyForDescription( $description )
		);

		$instance->clear();
	}

	public function testUnknownStrategyIsToReturnNull() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConditionBuilderStrategyFinder( $compoundConditionBuilder );

		$this->assertNull(
			$instance->findStrategyForDescription( $description )
		);

		$instance->clear();
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testFindStrategyForDescription( $description, $expected ) {

		$compoundConditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConditionBuilderStrategyFinder( $compoundConditionBuilder );

		$this->assertInstanceOf(
			$expected,
			$instance->findStrategyForDescription( $description )
		);

		$instance->clear();
	}

	public function descriptionProvider() {

		# 0
		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$provider[] = array(
			$valueDescription,
			'\SMW\SPARQLStore\QueryEngine\ConditionBuilder\ValueConditionBuilder'
		);

		return $provider;
	}

}
