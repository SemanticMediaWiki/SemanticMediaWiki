<?php

namespace SMW\Tests\ParserFunctions;

use SMW\ParserFunctions\InfoParserFunction;

/**
 * @covers \SMW\ParserFunctions\InfoParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.4
 *
 * @author mwjames
 */
class InfoParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\InfoParserFunction',
			new InfoParserFunction()
		);
	}

	public function testHandle() {

		$instance = new InfoParserFunction();

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$processedParam = $this->getMockBuilder( '\ParamProcessor\ProcessedParam' )
			->disableOriginalConstructor()
			->getMock();

		$processingResult = $this->getMockBuilder( '\ParamProcessor\ProcessingResult' )
			->disableOriginalConstructor()
			->getMock();

		$processingResult->expects( $this->any() )
			->method( 'getParameters' )
			->will( $this->returnValue( [
				'message'  => $processedParam,
				'icon'     => $processedParam ] ) );

		$this->assertInternalType(
			'string',
			$instance->handle( $parser, $processingResult )
		);
	}

}
