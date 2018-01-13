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
		
		$language = $this->getMockBuilder( '\ParamProcessor\ProcessedParam' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->any() )
			->method( 'getValue' )
			->will( $this->returnValue( 'en' ) );

		$processingResult = $this->getMockBuilder( '\ParamProcessor\ProcessingResult' )
			->disableOriginalConstructor()
			->getMock();

		$processingResult->expects( $this->any() )
			->method( 'getParameters' )
			->will( $this->returnValue( array(
				'language'   => $language,
				'format'     => $processedParam,
				'parameters' => $processedParam ) ) );

		$this->assertInternalType(
			'string',
			$instance->handle( $parser, $processingResult )
		);
	}

}
