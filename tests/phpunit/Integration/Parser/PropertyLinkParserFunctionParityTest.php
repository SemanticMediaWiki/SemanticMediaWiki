<?php

namespace SMW\Tests\Integration\Parser;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Tests\TestEnvironment;

/**
 * Proves that `{{#property_link:Property}}` renders output identical to the
 * equivalent `[[Property::@@@]]` annotation syntax. The JSONScript cases
 * p-0216/p-0466 pin representative fragments with substring assertions; this
 * test asserts full-output equality, so a divergence introduced into either
 * path — including the parser-function wrapper, which is not shared code —
 * is caught even when the shared property-link fragment is unchanged.
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class PropertyLinkParserFunctionParityTest extends TestCase {

	private TestEnvironment $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider equivalentSyntaxProvider
	 */
	public function testParserFunctionMatchesAnnotationSyntax( string $annotation, string $parserFunction ) {
		$annotationOutput = $this->parse( $annotation );

		$this->assertStringContainsString(
			'smw-property',
			$annotationOutput,
			'guard: the annotation is expected to render a property link'
		);

		$this->assertSame(
			$annotationOutput,
			$this->parse( $parserFunction )
		);
	}

	/**
	 * @return array<string,array{string,string}>
	 */
	public function equivalentSyntaxProvider(): array {
		return [
			'plain property' => [ '[[Foo::@@@]]', '{{#property_link:Foo}}' ],
			'custom caption' => [ '[[Foo::@@@|My caption]]', '{{#property_link:Foo|My caption}}' ],
			'no-link variant' => [ '[[Modification date::@@@|#]]', '{{#property_link:Modification date|#}}' ],
			'predefined property with tooltip' => [ '[[Modification date::@@@]]', '{{#property_link:Modification date}}' ],
		];
	}

	private function parse( string $wikitext ): string {
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		$parserOptions = ParserOptions::newFromAnon();

		return $parser->parse(
			$wikitext,
			Title::newFromText( 'PropertyLinkParserFunctionParityTest', NS_MAIN ),
			$parserOptions
		)->runOutputPipeline( $parserOptions )->getContentHolderText();
	}

}
