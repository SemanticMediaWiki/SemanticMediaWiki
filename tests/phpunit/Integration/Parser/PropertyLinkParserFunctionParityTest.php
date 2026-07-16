<?php

namespace SMW\Tests\Integration\Parser;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\ParserData;
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

	/**
	 * A caption is display text and must not annotate. The annotation syntax
	 * hands its caption to InTextAnnotationParser, which never rescans it, but
	 * a parser function returns wikitext that InTextAnnotationParser scans
	 * afterwards, so annotation syntax in a caption reaches that scan unless it
	 * is neutralized first.
	 *
	 * @dataProvider captionWithAnnotationSyntaxProvider
	 */
	public function testCaptionDoesNotAnnotate( string $wikitext ) {
		$this->assertNotContains(
			'Bar',
			$this->propertyKeys( $wikitext ),
			'a caption must not store an annotation of its own'
		);
	}

	/**
	 * @return array<string,array{string}>
	 */
	public function captionWithAnnotationSyntaxProvider(): array {
		return [
			'annotation' => [ '{{#property_link:Foo|[[Bar::Baz]]}}' ],
			'annotation with caption' => [ '{{#property_link:Foo|[[Bar::Baz|label]]}}' ],
			'annotation within text' => [ '{{#property_link:Foo|see [[Bar::Baz]] here}}' ],
			'nested annotation' => [ '{{#property_link:Foo|[[Foo::[[Bar::Baz]]]]}}' ],
		];
	}

	/**
	 * `[[SMW::off]]` disables annotation processing for the remainder of the
	 * text, so a caption carrying it would silently discard the annotations of
	 * an entire page.
	 */
	public function testCaptionCannotDisableSubsequentAnnotations() {
		$this->assertContains(
			'Baz',
			$this->propertyKeys( '{{#property_link:Foo|[[SMW::off]]}} [[Baz::Quux]]' ),
			'a caption must not disable annotation processing for what follows it'
		);
	}

	private function parse( string $wikitext ): string {
		$parserOptions = ParserOptions::newFromAnon();

		return $this->parserOutput( $wikitext, $parserOptions )
			->runOutputPipeline( $parserOptions )
			->getContentHolderText();
	}

	/**
	 * @return string[] the keys of the properties annotated by `$wikitext`
	 */
	private function propertyKeys( string $wikitext ): array {
		$title = $this->newTitle();
		$parserData = new ParserData(
			$title,
			$this->parserOutput( $wikitext, ParserOptions::newFromAnon(), $title )
		);

		$keys = [];

		foreach ( $parserData->getSemanticData()->getProperties() as $property ) {
			$keys[] = $property->getKey();
		}

		return $keys;
	}

	private function parserOutput( string $wikitext, ParserOptions $parserOptions, ?Title $title = null ) {
		return MediaWikiServices::getInstance()->getParserFactory()->create()->parse(
			$wikitext,
			$title ?? $this->newTitle(),
			$parserOptions
		);
	}

	private function newTitle(): Title {
		return Title::newFromText( 'PropertyLinkParserFunctionParityTest', NS_MAIN );
	}

}
