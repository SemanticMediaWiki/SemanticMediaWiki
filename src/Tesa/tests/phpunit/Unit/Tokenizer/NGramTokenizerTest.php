<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\NGramTokenizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\Tokenizer\NGramTokenizer
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class NGramTokenizerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\NGramTokenizer',
			new NGramTokenizer()
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $ngram, $expected ) {
		$instance = new NGramTokenizer( null, $ngram );

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);

		$this->assertFalse(
			$instance->isWordTokenizer()
		);
	}

	public function testTokenizeWithStartEndMarker() {
		// http://cloudmark.github.io/Language-Detection
		$string = 'TEXT';

		$expected = [
			'_tex',
			'text',
			'ext_',
			'xt__',
			't___'
		];

		$instance = new NGramTokenizer( null, 4 );
		$instance->withMarker( true );

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function testTokenizeWithStartEndMarker2() {
		$string = '教授は';

		$expected = [
			'_教授',
			'教授は',
			'授は_',
			'は__'
		];

		$instance = new NGramTokenizer( null, 3 );
		$instance->withMarker( true );

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function testTokenizeWithOption() {
		$string = '红色中华';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'setOption' );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $string )
			->willReturn( [ $string ] );

		$instance = new NGramTokenizer( $tokenizer );

		$instance->setOption(
			NGramTokenizer::REGEX_EXEMPTION,
			[ 'Foo' ]
		);

		$this->assertEquals(
			[ '红色', '色中', '中华' ],
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {
		$provider[] = [
			'TEXT',
			'4',
			[
				'text'
			]
		];

		$provider[] = [
			'12345678',
			'2',
			[
				'12',
				'23',
				'34',
				'45',
				'56',
				'67',
				'78'
			]
		];

		$provider[] = [
			'12345678',
			'3',
			[
				'123',
				'234',
				'345',
				'456',
				'567',
				'678'
			]
		];

		$provider[] = [
			'hello',
			'3',
			[
				'hel',
				'ell',
				'llo'
			]
		];

		$provider[] = [
			'Hello World!',
			'3',
			[
				'hel',
				'ell',
				'llo',
				'lo ',
				'o w',
				' wo',
				'wor',
				'orl',
				'rld',
				'ld!'
			]
		];

		$provider[] = [
			'Новости',
			'3',
			[
				'нов',
				'ово',
				'вос',
				'ост',
				'сти'
			]
		];

		$provider[] = [
			'1時36分更新',
			'3',
			[
				'1時3',
				'時36',
				'36分',
				'6分更',
				'分更新'
			]
		];

		$provider[] = [
			'こんにちは世界！',
			'2',
			[
				'こん',
				'んに',
				'にち',
				'ちは',
				'は世',
				'世界',
				'界！'
			]
		];

		return $provider;
	}

}
