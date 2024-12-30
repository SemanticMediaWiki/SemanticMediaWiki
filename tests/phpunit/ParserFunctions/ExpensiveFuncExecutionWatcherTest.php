<?php

namespace SMW\Tests\ParserFunctions;

use SMW\ParserFunctions\ExpensiveFuncExecutionWatcher;

/**
 * @covers \SMW\ParserFunctions\ExpensiveFuncExecutionWatcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
class ExpensiveFuncExecutionWatcherTest extends \PHPUnit\Framework\TestCase {

	private $parserData;

	protected function setUp(): void {
		parent::setUp();

		$this->parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\ParserFunctions\ExpensiveFuncExecutionWatcher',
			new ExpensiveFuncExecutionWatcher( $this->parserData )
		);
	}

	public function testHasReachedExpensiveLimit() {
		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->willReturn( 42 );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->willReturn( 100 );

		$this->parserData->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$instance = new ExpensiveFuncExecutionWatcher(
			$this->parserData
		);

		$instance->setExpensiveExecutionLimit( 1 );

		$this->assertTrue(
			$instance->hasReachedExpensiveLimit( $query )
		);
	}

	public function testIncrementExpensiveCountOnExsitingCounter() {
		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'getExtensionData' )
			->willReturn( 42 );

		$parserOutput->expects( $this->once() )
			->method( 'setExtensionData' )
			->with(
				ExpensiveFuncExecutionWatcher::EXPENSIVE_COUNTER,
				43 );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->willReturn( 100 );

		$query->expects( $this->once() )
			->method( 'getOption' )
			->willReturn( 100 );

		$this->parserData->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$instance = new ExpensiveFuncExecutionWatcher(
			$this->parserData
		);

		$instance->setExpensiveThreshold( 1 );
		$instance->setExpensiveExecutionLimit( 1 );

		$instance->incrementExpensiveCount( $query );
	}

	public function testIncrementExpensiveCountOnNull() {
		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'setExtensionData' )
			->with(
				ExpensiveFuncExecutionWatcher::EXPENSIVE_COUNTER,
				1 );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->willReturn( 100 );

		$query->expects( $this->once() )
			->method( 'getOption' )
			->willReturn( 100 );

		$this->parserData->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$instance = new ExpensiveFuncExecutionWatcher(
			$this->parserData
		);

		$instance->setExpensiveThreshold( 1 );
		$instance->setExpensiveExecutionLimit( 1 );

		$instance->incrementExpensiveCount( $query );
	}

}
