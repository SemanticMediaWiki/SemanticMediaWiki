<?php

namespace SMW\Tests\ParserFunctions;

use ParamProcessor\ProcessedParam;
use ParamProcessor\ProcessingResult;
use SMW\ParserFunctions\DocumentationParserFunction;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\ParserFunctions\DocumentationParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.4
 *
 * @author mwjames
 */
class DocumentationParserFunctionTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testHandle() {
		$instance = new DocumentationParserFunction();

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$processedParam = $this->getMockBuilder( ProcessedParam::class )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( ProcessedParam::class )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->any() )
			->method( 'getValue' )
			->willReturn( 'en' );

		$processingResult = $this->getMockBuilder( ProcessingResult::class )
			->disableOriginalConstructor()
			->getMock();

		$processingResult->expects( $this->any() )
			->method( 'getParameters' )
			->willReturn( [
				'language'   => $language,
				'format'     => $processedParam,
				'parameters' => $processedParam ]
			);

		$this->assertIsString(

			$instance->handle( $parser, $processingResult )
		);
	}

}
