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

		// The truncation operator only has meaning in a search term.
		// When indexing, an asterisk is punctuation and must not keep
		// a token that is below the minimum length.
		yield 'asterisk does not retain a short token when indexing' => [
			'a * b',
			''
		];

		yield 'multiplication signs do not retain digits when indexing' => [
			'2 * pi * r',
			''
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
			'foo*'
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
			'foo* bar'
		];

		yield 'plus wildcard combo' => [
			'+foo*, *bar',
			'+foo* bar'
		];

		yield 'two letters with wildcard' => [
			'be*',
			'be*'
		];

		yield 'single asterisk wildcard' => [
			'*',
			''
		];

		yield 'duplicate operators' => [
			'++appl* ~~doctor --away',
			'+appl* ~doctor -away'
		];

		yield 'operators in wrong positions' => [
			'*appl+ *doctor- *away~',
			'appl doctor away'
		];

		yield 'illegal operator sequences' => [
			'-+peach+* decidu*+-',
			'peach decidu*'
		];

		yield 'at sign as delimiter' => [
			'name@email',
			'name email'
		];

		yield 'required term with leading wildcard' => [
			'+*apple* banana',
			'+apple* banana'
		];

		yield 'excluded term with leading wildcard' => [
			'-*apple* pear',
			'-apple* pear'
		];

		yield 'relevance operator with leading wildcard' => [
			'~*apple* pear',
			'~apple* pear'
		];

		yield 'doubled leading wildcard' => [
			'**apple',
			'apple'
		];

		yield 'doubled trailing wildcard' => [
			'apple**',
			'apple*'
		];

		yield 'comma directly before wildcard' => [
			'foo,*bar',
			'foo bar'
		];

		yield 'operator run reducible only in several passes' => [
			'-+~-apple',
			'-apple'
		];

		yield 'operator run reducible only in several passes, trailing' => [
			'apple ~+-~pear',
			'apple ~pear'
		];

		yield 'trailing operator after a quoted phrase' => [
			'"apple" -',
			'"apple"'
		];

		yield 'trailing operator after an embedded quote' => [
			'find "this" +',
			'find"this"'
		];

		yield 'quote left behind by a filtered short token' => [
			'+ab" +pear',
			'+pear'
		];

		yield 'required phrase' => [
			'+"apple pear"',
			'+"apple pear"'
		];

		yield 'excluded phrase' => [
			'-"apple pear"',
			'-"apple pear"'
		];

		yield 'phrase followed by a required term' => [
			'"apple pear" +kiwi',
			'"apple pear"+kiwi'
		];

		yield 'wildcard after a delimiter that is dropped' => [
			'apple.*',
			'apple*'
		];

		yield 'wildcard after a bracket that is dropped' => [
			'(test)*',
			'test*'
		];


	}

}
