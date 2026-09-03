<?php

namespace SMW\Tests\Unit\ParserFunctions;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\StripState;
use MediaWiki\Title\Title;
use ParamProcessor\ProcessedParam;
use ParamProcessor\ProcessingResult;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Outputs;
use SMW\ParserFunctions\InfoParserFunction;
use SMW\Tests\TestEnvironment;

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

	private TestEnvironment $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		Outputs::reset();
	}

	protected function tearDown(): void {
		Outputs::reset();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

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

	public function testHtmlPassCommitsTooltipResourcesToTheParser() {
		$parser = $this->newParser( Parser::OT_HTML, NS_MAIN );

		$result = $this->handleInfo( $parser, 'Some tooltip text' );

		$this->assertStringContainsString(
			'smw-highlighter',
			$result,
			'guard: #info is expected to render a highlighter'
		);

		$this->assertContains(
			'ext.smw.tooltip',
			$parser->getOutput()->getModules()
		);
	}

	public function testNonHtmlPassLeavesTooltipResourcesBufferedForTheRenderingParse() {
		$parser = $this->newParser( Parser::OT_PREPROCESS, NS_MAIN );

		$result = $this->handleInfo( $parser, 'Some tooltip text' );

		$this->assertStringContainsString(
			'smw-highlighter',
			$result,
			'guard: #info is expected to render a highlighter'
		);

		$this->assertNotContains(
			'ext.smw.tooltip',
			$parser->getOutput()->getModules(),
			'the ParserOutput of a non-rendering pass is discarded and must not receive the modules'
		);

		$renderingParseOutput = new ParserOutput();
		Outputs::commitToParserOutput( $renderingParseOutput );

		$this->assertContains(
			'ext.smw.tooltip',
			$renderingParseOutput->getModules(),
			'the requirements must stay buffered for the rendering parse that follows'
		);
	}

	public function testSpecialPageCommitsTooltipResourcesToTheOutputPage() {
		$parser = $this->newParser( Parser::OT_PREPROCESS, NS_SPECIAL );
		$output = $this->newOutputPage();

		$this->handleInfo( $parser, 'Some tooltip text' );

		$this->assertContains(
			'ext.smw.tooltip',
			$output->getModules(),
			'a special page carries the requirements on its OutputPage, whatever the pass'
		);
	}

	private function handleInfo( Parser $parser, string $message ): string {
		return ( new InfoParserFunction() )->handle( $parser, $this->processingResult( [
			'message' => $message,
			'icon' => 'info',
			'max-width' => '',
			'theme' => ''
		] ) );
	}

	private function processingResult( array $values ): ProcessingResult {
		$parameters = [];

		foreach ( $values as $name => $value ) {
			$parameter = $this->getMockBuilder( ProcessedParam::class )
				->disableOriginalConstructor()
				->getMock();

			$parameter->method( 'getValue' )
				->willReturn( $value );

			$parameters[$name] = $parameter;
		}

		$processingResult = $this->getMockBuilder( ProcessingResult::class )
			->disableOriginalConstructor()
			->getMock();

		$processingResult->method( 'getParameters' )
			->willReturn( $parameters );

		return $processingResult;
	}

	private function newOutputPage(): OutputPage {
		$context = new RequestContext();
		$context->setTitle( $this->newTitle( NS_SPECIAL ) );

		$output = $context->getOutput();
		$this->testEnvironment->withConfiguration( [ 'wgOut' => $output ] );

		return $output;
	}

	private function newTitle( int $namespace ): Title {
		return MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __CLASS__, $namespace );
	}

	private function newParser( int $outputType, int $namespace ): Parser {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$parser->method( 'getTitle' )
			->willReturn( $this->newTitle( $namespace ) );

		$parser->method( 'getOutput' )
			->willReturn( new ParserOutput() );

		$parser->method( 'getOutputType' )
			->willReturn( $outputType );

		$stripState = $this->getMockBuilder( StripState::class )
			->disableOriginalConstructor()
			->getMock();

		$stripState->method( 'unstripBoth' )
			->willReturnArgument( 0 );

		$parser->method( 'getStripState' )
			->willReturn( $stripState );

		return $parser;
	}

}
