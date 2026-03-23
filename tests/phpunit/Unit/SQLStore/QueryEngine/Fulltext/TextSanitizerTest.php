<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine\Fulltext;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TextSanitizerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TextSanitizer::class,
			new TextSanitizer()
		);
	}

	public function testGetVersions() {
		$instance = new TextSanitizer();
		$versions = $instance->getVersions();

		$this->assertArrayHasKey( 'ICU (Intl) PHP-extension', $versions );
		$this->assertArrayHasKey( 'LanguageDetector', $versions );
	}

	/**
	 * @dataProvider sanitizeProvider
	 */
	public function testSanitize( $text, $expected ) {
		$instance = new TextSanitizer();
		$instance->setMinTokenSize( 3 );

		$this->assertEquals(
			$expected,
			$instance->sanitize( $text )
		);
	}

	public function testSanitizeGreekTransliteration() {
		$instance = new TextSanitizer();
		$instance->setMinTokenSize( 3 );

		$result = $instance->sanitize( 'Ελληνική' );

		$this->assertDoesNotMatchRegularExpression(
			'/\p{Greek}/u',
			$result,
			'Greek characters should be transliterated to Latin'
		);
	}

	public function testSanitizeWithStopwords() {
		$instance = new TextSanitizer();
		$instance->setMinTokenSize( 3 );
		$instance->setLanguageDetection( [
			'TextCatLanguageDetector' => [ 'en', 'de', 'fr', 'es' ]
		] );

		$result = $instance->sanitize(
			'In computing, stop words are words which are filtered out before or after processing'
		);

		// Common English stopwords like "are", "which", "or" should be removed
		$this->assertStringNotContainsString( ' are ', ' ' . $result . ' ' );
	}

	/**
	 * @dataProvider operatorSpacingProvider
	 */
	public function testOperatorSpacing( $text, $expected ) {
		$instance = new TextSanitizer();
		$instance->setMinTokenSize( 3 );

		$this->assertEquals(
			$expected,
			$instance->sanitize( $text, true )
		);
	}

	public static function sanitizeProvider() {
		yield 'basic latin' => [
			'Hello World',
			'hello world'
		];

		yield 'diacritics' => [
			'café résumé',
			'cafe resume'
		];

		// The generic regex tokenizer splits on non-numeric dots,
		// so example.com becomes "example com"
		yield 'URL stripping' => [
			'visit http://example.com today',
			'visit example com today'
		];

		yield 'fullwidth ASCII to halfwidth lowercase' => [
			'Ｈｅｌｌｏ Ｗｏｒｌｄ',
			'hello world'
		];

		yield 'diacritics ß and umlaut' => [
			'Straße München',
			'strasse munchen'
		];

		yield 'empty string' => [
			'',
			''
		];

		yield 'min token filtering' => [
			'I am a test',
			'test'
		];
	}

	public static function operatorSpacingProvider() {
		yield 'wildcard minus' => [
			'foo* - bar',
			'foo* -bar'
		];

		yield 'wildcard plus' => [
			'foo* + bar',
			'foo* +bar'
		];

		yield 'trailing wildcard' => [
			'foo *',
			'foo*'
		];

		yield 'surrounding wildcards' => [
			'* foo *',
			'*foo*'
		];

		yield 'plus and wildcard combo' => [
			'+foo* -bar',
			'+foo* -bar'
		];

		yield 'wildcard tilde' => [
			'+foo* ~ bar',
			'+foo* ~bar'
		];

		yield 'adjacent wildcards' => [
			'*foo* bar',
			'*foo*bar'
		];

		yield 'plus wildcard combo' => [
			'+foo*, *bar',
			'+foo*,*bar'
		];
	}

}
