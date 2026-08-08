<?php

namespace SMW\Tests\Integration\Parser;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Template\TemplateExpander;
use SMW\Parser\RecursiveTextProcessor;

/**
 * @covers \SMW\Parser\RecursiveTextProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.2.1
 */
class RecursiveTextProcessorIntegrationTest extends TestCase {

	public function testRecursivePreprocessExpandsTextOnAParserPrimedWithoutAPage() {
		$parser = $this->newParserPrimedWithoutAPage();

		$instance = new RecursiveTextProcessor( $parser );
		$instance->uniqid();

		$this->assertSame(
			'[[SMW::off]]5[[SMW::on]]',
			$instance->recursivePreprocess( '{{formatnum:5}}' )
		);
	}

	/**
	 * A file export runs its templates through TemplateExpander, which feeds the
	 * parser's own placeholder title back into Parser::preprocess. That sets the
	 * parser options while leaving the page unset, which is the state issue #7055
	 * was reported against.
	 */
	private function newParserPrimedWithoutAPage(): Parser {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();

		( new TemplateExpander( $parser ) )->expand( 'Foo' );

		$this->assertNotNull(
			$parser->getOptions(),
			'expected TemplateExpander to have set the parser options'
		);

		$this->assertTrue(
			$parser->getTitle()->isSpecial( 'Badtitle' ),
			'expected the parser to be left without a page'
		);

		return $parser;
	}

}
