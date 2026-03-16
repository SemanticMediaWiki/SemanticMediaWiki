<?php

namespace SMW\Tests\ParserFunctions;

use MediaWiki\Parser\Parser;
use ParamProcessor\ProcessedParam;
use ParamProcessor\ProcessingResult;
use PHPUnit\Framework\TestCase;
use SMW\ParserFunctions\DocumentationParserFunction;

/**
 * @covers \SMW\ParserFunctions\DocumentationParserFunction
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since  2.4
 *
 * @author mwjames
 */
class DocumentationParserFunctionTest extends TestCase {

	public function testHandle() {
		$instance = new DocumentationParserFunction();

		$parser = $this->getMockBuilder( Parser::class )
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
