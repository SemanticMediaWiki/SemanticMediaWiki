<?php

namespace SMW\Tests\Integration\Parser;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Outputs;
use SMW\Tests\TestEnvironment;

/**
 * Mirrors the two-pass flow of Special:ExpandTemplates with a content-page
 * context title: the input is expanded with `Parser::preprocess()` and the
 * result is rendered by `Parser::parse()` on the same parser instance. The
 * tooltip modules requested while a parser function expands must reach the
 * ParserOutput of the rendering parse, which is the one that is displayed;
 * the ParserOutput of the preprocess pass is discarded (#7109).
 *
 * @covers \SMW\MediaWiki\Outputs
 * @covers \SMW\ParserFunctions\InfoParserFunction
 * @covers \SMW\ParserFunctions\PropertyLinkParserFunction
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.2.2
 */
class TooltipResourceDeliveryTest extends TestCase {

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

	/**
	 * @dataProvider tooltipFunctionProvider
	 */
	public function testFunctionExpandedDuringPreprocessDeliversTooltipModulesToTheRenderingParse( string $wikitext ) {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$title = Title::newFromText( 'TooltipResourceDeliveryTest', NS_MAIN );
		$parserOptions = ParserOptions::newFromAnon();

		$expanded = $parser->preprocess( $wikitext, $title, $parserOptions );
		$parserOutput = $parser->parse( $expanded, $title, $parserOptions );

		$this->assertStringContainsString(
			'smw-highlighter',
			$parserOutput->getRawText(),
			'guard: the rendering parse is expected to output a highlighter'
		);

		$this->assertContains(
			'ext.smw.tooltip',
			$parserOutput->getModules()
		);
	}

	/**
	 * @return array<string,array{string}>
	 */
	public function tooltipFunctionProvider(): array {
		return [
			'property link' => [ '{{#property_link:Modification date}}' ],
			'info' => [ '{{#info:Some tooltip text}}' ],
		];
	}

}
