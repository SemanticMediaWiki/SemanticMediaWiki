<?php

namespace Onoi\Tesa\Tests\Integration;

use Onoi\Tesa\SanitizerFactory;

/**
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class JaTokenizerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider icuTextProvider
	 */
	public function testIcuWordBoundaryTokenizer( $text, $expected ) {

		$sanitizerFactory = new SanitizerFactory();

		$tokenier = $sanitizerFactory->newIcuWordBoundaryTokenizer(
			$sanitizerFactory->newGenericRegExTokenizer()
		);

		if ( !$tokenier->isAvailable() || INTL_ICU_VERSION != '54.1' ) {
			$this->markTestSkipped( 'ICU extension is not available or does not match the expected version constraint.' );
		}

		$this->assertEquals(
			$expected,
			$tokenier->tokenize( $text )
		);
	}

	/**
	 * @dataProvider tinyTextProvider
	 */
	public function testJaTinySegmenterTokenizer( $text, $expected ) {

		$sanitizerFactory = new SanitizerFactory();

		$tokenier = $sanitizerFactory->newJaTinySegmenterTokenizer(
			 $sanitizerFactory->newPunctuationRegExTokenizer()
		);

		$this->assertEquals(
			$expected,
			$tokenier->tokenize( $text )
		);
	}

	public function icuTextProvider() {

		// https://github.com/NaturalNode/natural/blob/master/spec/tokenizer_ja_spec.js

		$provider[] = array(
			"計算機科学における字句解析 (じくかいせき、英: Lexical Analysis) とは、ソースコードを構成する文字の並びを、トークン (token) の並びに変換することをいう。\n" .
			"ここでいう「トークン」とは、意味を持つコードの最小単位のこと。字句解析を行うプログラムは、字句解析器 (lexical analyzer, 略称：lexer) と呼ばれる。\n" .
			"字句解析器はスキャナ (scanner) とトークナイザ (tokenizer) から構成される。\n",
			//
			array(
				'計算機', '科学', 'における', '字句', '解析', 'じ', 'く', 'かい',
				'せき', '、', '英', 'Lexical', 'Analysis', 'と', 'は', '、', 'ソース', 'コード', 'を',
				'構成', 'する', '文字', 'の', '並び', 'を', '、', 'トーク',
				'ン', 'token', 'の', '並びに', '変換', 'する', 'こと', 'を',
				'いう', '。', 'ここ', 'で', 'いう', '「', 'トーク', 'ン',
				'」', 'と', 'は', '、', '意味', 'を', '持つ', 'コード',
				'の', '最小', '単位', 'の', 'こと', '。', '字句', '解析',
				'を', '行う', 'プログラム', 'は', '、', '字句', '解析', '器',
				'lexical', 'analyzer', '略称', '：',
				'lexer', 'と', '呼ばれる', '。', '字句', '解析', '器', 'は',
				'スキャナ', 'scanner', 'と', 'トーク','ナ', 'イザ', 'tokenizer', 'から',
				'構成', 'さ', 'れる', '。'
			)
		);

		return $provider;
	}

	public function tinyTextProvider() {

		// https://github.com/NaturalNode/natural/blob/master/spec/tokenizer_ja_spec.js
		/*
			['計算', '機科', '学', 'に', 'おける', '字句', '解析',
			'じくかい', 'せき', '英', 'Lexical', 'Analysis', 'と', 'は', 'ソースコード',
			'を', '構成', 'する', '文字', 'の', '並び', 'を', 'トークン', 'token', 'の',
			'並び', 'に', '変換', 'する', 'こと', 'を', 'いう', 'ここ', 'でいう', 'トークン',
			'と', 'は', '意味', 'を', '持つ', 'コード', 'の', '最小', '単位', 'の', 'こと',
			'字句', '解析', 'を', '行う', 'プログラム', 'は', '字句', '解析', '器', 'lexical',
			'analyzer', '略称', 'lexer', 'と', '呼ば', 'れる', '字句', '解析', '器', 'は',
			'スキャナ', 'scanner', 'と', 'トークナイザ', 'tokenizer', 'から', '構成', 'さ',
			'れる']
		 */

		$provider[] = array(
			"計算機科学における字句解析 (じくかいせき、英: Lexical Analysis) とは、ソースコードを構成する文字の並びを、トークン (token) の並びに変換することをいう。\n" .
			"ここでいう「トークン」とは、意味を持つコードの最小単位のこと。字句解析を行うプログラムは、字句解析器 (lexical analyzer, 略称：lexer) と呼ばれる。\n" .
			"字句解析器はスキャナ (scanner) とトークナイザ (tokenizer) から構成される。\n",
			//
			array(
				'計算', '機科', '学', 'に', 'おける', '字句', '解析', 'じくかい','せき','英',
				'Lexical', 'Analysis', 'と', 'は', 'ソースコード', 'を', '構成', 'する',
				'文字', 'の', '並び', 'を', 'トークン', 'token', 'の', '並び', 'に', '変換',
				'する', 'こと', 'をいう', 'ここ', 'でいう', 'トークン', 'と', 'は', '意味', 'を',
				'持つ', 'コード', 'の', '最小', '単位', 'の', 'こと', '字句', '解析', 'を',
				'行う', 'プログラム', 'は', '字句', '解析', '器', 'lexical', 'analyzer',
				'略称', 'lexer', 'と', '呼ば', 'れる', '字句', '解析', '器', 'は', 'スキャナ', 'scanner',
				'と', 'トークナイザ', 'tokenizer', 'から', '構成', 'さ', 'れる',
			)
		);

		return $provider;
	}

}
