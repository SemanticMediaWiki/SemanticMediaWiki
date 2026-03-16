<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\QueryEngine\Fulltext\JaTinySegmenterTokenizer;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\JaTinySegmenterTokenizer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 6.1.0
 */
class JaTinySegmenterTokenizerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			JaTinySegmenterTokenizer::class,
			new JaTinySegmenterTokenizer()
		);
	}

	/**
	 * @dataProvider japaneseTextProvider
	 */
	public function testTokenize( $text, $expected ) {
		$tokenizer = new JaTinySegmenterTokenizer();

		$this->assertEquals(
			$expected,
			$tokenizer->tokenize( $text )
		);
	}

	public function testTokenizeEmptyString() {
		$tokenizer = new JaTinySegmenterTokenizer();

		$this->assertSame(
			[],
			$tokenizer->tokenize( '' )
		);
	}

	public static function japaneseTextProvider() {
		// Raw segmenter output includes punctuation — the fulltext pipeline
		// filters these through additional tokenizers before indexing
		yield 'simple japanese' => [
			'計算機科学における字句解析',
			[
				'計算', '機科', '学', 'に', 'おける', '字句', '解析',
			]
		];

		yield 'kanji compound' => [
			'東京都に住んでいます',
			[ '東京都', 'に', '住ん', 'で', 'い', 'ます' ]
		];
	}

}
