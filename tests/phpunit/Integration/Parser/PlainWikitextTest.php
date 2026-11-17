<?php

namespace SMW\Tests\Integration\Parser;

use SMW\Localizer\Message;
use SMW\Parser\PlainWikitext;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Unit\Parser\PlainWikitextTest as PlainWikitextUnitTest;

/**
 * Needs MediaWiki's parser to compare its output with the text.
 *
 * @covers \SMW\Parser\PlainWikitext
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PlainWikitextTest extends SMWIntegrationTestCase {

	/**
	 * Letters, combining marks and digits from several scripts
	 */
	private const WORD_TOKENS = [ 'a', 'Z', 'é', "e\u{0301}", 'я', '5', '٣', '²' ];

	/**
	 * Word tokens weighted against spaces, punctuation and the characters and
	 * words the parser treats differently from plain letters
	 */
	private const TOKENS = [ ...self::WORD_TOKENS, ...self::WORD_TOKENS, ...self::WORD_TOKENS, ' ', ' ', ' ', ' ',
		'.', ',', '-', '(', ')', '/', "'", '"', '‘', '’', '“', '”', '?', '!', ':', ';', '%', '&', '°', '…', '–', '—',
		"\n", '<', '[', '»', '----', '&amp;', '&nbsp;', "\u{0338}", 'ISBN', 'RFC', 'PMID', 'tel', 'https', '__' ];

	public function testParserRendersPlainTextAsTheEscapedText() {
		$plainTexts = array_filter( $this->candidateTexts(), [ PlainWikitext::class, 'isPlain' ] );

		$this->assertGreaterThan( 1000, count( $plainTexts ) );
		$this->assertSame( [], $this->textsParsedDifferently( $plainTexts ) );
	}

	/**
	 * @return string[] the unit test's plain examples and deterministic mixes of `TOKENS`
	 */
	private function candidateTexts(): array {
		$texts = array_column( iterator_to_array( PlainWikitextUnitTest::plainTextProvider(), false ), 0 );

		for ( $i = 0; $i < 20000; $i++ ) {
			$text = self::WORD_TOKENS[$this->pseudoRandom( "first $i", count( self::WORD_TOKENS ) )];
			for ( $k = $this->pseudoRandom( "length $i", 10 ); $k > 0; $k-- ) {
				$text .= self::TOKENS[$this->pseudoRandom( "$i $k", count( self::TOKENS ) )];
			}
			$texts[] = $text;
		}

		return array_unique( $texts );
	}

	/**
	 * md5 rather than crc32, whose low bits correlate across similar seeds
	 */
	private function pseudoRandom( string $seed, int $limit ): int {
		return hexdec( substr( md5( $seed ), 0, 8 ) ) % $limit;
	}

	/**
	 * @param string[] $texts
	 *
	 * @return string[] parser output keyed by the text whose escaped form it differs from
	 */
	private function textsParsedDifferently( array $texts ): array {
		$differences = [];

		foreach ( $texts as $text ) {
			$parsed = Message::get( [ 'smw-parse', $text ], Message::PARSE );

			if ( $parsed !== htmlspecialchars( $text, ENT_NOQUOTES ) ) {
				$differences[$text] = $parsed;
			}
		}

		return $differences;
	}

}
