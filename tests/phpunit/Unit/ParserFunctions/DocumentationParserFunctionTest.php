<?php

namespace SMW\Tests\ParserFunctions;

use SMW\ParserFunctions\DocumentationParserFunction;

/**
 * @covers \SMW\ParserFunctions\DocumentationParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.4
 *
 * @author mwjames
 */
class DocumentationParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\DocumentationParserFunction',
			new DocumentationParserFunction()
		);
	}

	public function testHandle() {

		$instance = new DocumentationParserFunction();

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
			->will( $this->returnValue( array(
				'language'   => $processedParam,
				'format'     => $processedParam,
				'parameters' => $processedParam ) ) );

		$this->assertInternalType(
			'string',
			$instance->handle( $parser, $processingResult )
		);
	}

}
