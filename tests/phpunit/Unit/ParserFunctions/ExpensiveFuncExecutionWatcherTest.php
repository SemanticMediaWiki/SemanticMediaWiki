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
class ExpensiveFuncExecutionWatcherTest extends \PHPUnit_Framework_TestCase {

	private $parserData;

	protected function setUp() {
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
			->will( $this->returnValue( 42 ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->will( $this->returnValue( 100 ) );

		$this->parserData->expects( $this->once() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

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
			->will( $this->returnValue( 42 ) );

		$parserOutput->expects( $this->once() )
			->method( 'setExtensionData' )
			->with(
				$this->equalTo( ExpensiveFuncExecutionWatcher::EXPENSIVE_COUNTER ),
				$this->equalTo( 43 ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->will( $this->returnValue( 100 ) );

		$query->expects( $this->once() )
			->method( 'getOption' )
			->will( $this->returnValue( 100 ) );

		$this->parserData->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

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
				$this->equalTo( ExpensiveFuncExecutionWatcher::EXPENSIVE_COUNTER ),
				$this->equalTo( 1 ) );

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->will( $this->returnValue( 100 ) );

		$query->expects( $this->once() )
			->method( 'getOption' )
			->will( $this->returnValue( 100 ) );

		$this->parserData->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$instance = new ExpensiveFuncExecutionWatcher(
			$this->parserData
		);

		$instance->setExpensiveThreshold( 1 );
		$instance->setExpensiveExecutionLimit( 1 );

		$instance->incrementExpensiveCount( $query );
	}

}
