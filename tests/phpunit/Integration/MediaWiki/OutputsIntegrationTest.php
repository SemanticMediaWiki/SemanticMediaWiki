<?php

namespace SMW\Tests\Integration\MediaWiki;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * Exercises the real `Outputs` buffer lifecycle through an actual
 * `Parser::parse()`: the `ParserClearState` hook registers the parse, an SMW
 * construct registers a resource module, and the module must reach the parse's
 * `ParserOutput` (see #7009).
 *
 * @covers \SMW\MediaWiki\Outputs
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class OutputsIntegrationTest extends SMWIntegrationTestCase {

	/**
	 * A module registered while a real `Parser::parse()` is on the stack must
	 * be committed to that parse's `ParserOutput`. `{{#info:}}` registers the
	 * tooltip module via `Outputs::requireResource()` and needs no query.
	 */
	public function testResourceModuleReachesParserOutput() {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();

		$output = $parser->parse(
			'{{#info: tooltip text }}',
			Title::newFromText( 'OutputsIntegrationTest' ),
			ParserOptions::newFromAnon()
		);

		$this->assertContains(
			'ext.smw.tooltip',
			$output->getModules(),
			'A module registered during a real Parser::parse() must reach the ParserOutput'
		);
	}
}
