<?php

namespace SMW\Tests\Unit\ParserFunctions;

use MediaWiki\Parser\Parser;
use ParamProcessor\ProcessedParam;
use ParamProcessor\ProcessingResult;
use PHPUnit\Framework\TestCase;
use SMW\ParserFunctions\InfoParserFunction;

/**
 * @covers \SMW\ParserFunctions\InfoParserFunction
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since  2.4
 *
 * @author mwjames
 */
class InfoParserFunctionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			InfoParserFunction::class,
			new InfoParserFunction()
		);
	}

	public function testHandle() {
		$instance = new InfoParserFunction();

		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$processedParam = $this->getMockBuilder( ProcessedParam::class )
			->disableOriginalConstructor()
			->getMock();

		$processingResult = $this->getMockBuilder( ProcessingResult::class )
			->disableOriginalConstructor()
			->getMock();

		$processingResult->expects( $this->any() )
			->method( 'getParameters' )
			->willReturn( [
				'message'  => $processedParam,
				'max-width'  => $processedParam,
				'theme'  => $processedParam,
				'icon'     => $processedParam ] );

		$this->assertIsString(

			$instance->handle( $parser, $processingResult )
		);
	}

}
