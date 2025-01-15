<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\IcuWordBoundaryTokenizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\Tokenizer\IcuWordBoundaryTokenizer
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class IcuWordBoundaryTokenizerTest extends TestCase {

	protected function setUp(): void {
		$instance = new IcuWordBoundaryTokenizer();

		if ( !$instance->isAvailable() || INTL_ICU_VERSION != '54.1' ) {
			$this->markTestSkipped( 'ICU extension is not available or does not match the expected version constraint.' );
		}
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\IcuWordBoundaryTokenizer',
			new IcuWordBoundaryTokenizer()
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $expected ) {
		$instance = new IcuWordBoundaryTokenizer();

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function testSetOption() {
		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'setOption' );

		$instance = new IcuWordBoundaryTokenizer(
			$tokenizer
		);

		$instance->setOption(
			IcuWordBoundaryTokenizer::REGEX_EXEMPTION,
			[ 'Foo' ]
		);
	}

	public function testGeneralSetters() {
		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new IcuWordBoundaryTokenizer(
			$tokenizer
		);

		$instance->setLocale( 'en' );
		$instance->setWordTokenizerAttribute( false );

		$this->assertFalse(
			$instance->isWordTokenizer()
		);
	}

	public function stringProvider() {
		$provider[] = [
			"安全テスト",
			[ '安全', 'テスト' ]
		];

		// Would expect 'すもも', 'も', 'もも', 'も', 'もも', 'の', 'うち', '。'
		$provider[] = [
			"すもももももももものうち。",
			[ 'すもも', 'も', 'も', 'も', 'も', 'も', 'もの', 'うち', '。' ]
		];

		$provider[] = [
			"李も桃も桃のうち。",
			[ '李', 'も', '桃', 'も', '桃', 'の', 'うち', '。' ]
		];

		$provider[] = [
			"إسرائيل",
			[ 'إسرائيل' ]
		];

		$provider[] = [
			"검색엔ㅇㅏ진",
			[ '검색엔', 'ㅇㅏ', '진' ]
		];

		$provider[] = [
			"검색엔ㅇㅏ진1234abcdfrA",
			[ '검색엔', 'ㅇㅏ', '진', '1234abcdfrA' ]
		];

		$provider[] = [
			"1234abcdfrA",
			[ '1234abcdfrA' ]
		];

		$provider[] = [
			"公明執ようなＳＮＳもストーカー行為の対象に",
			[
				'公明', '執よう', 'な', 'ＳＮＳ', 'も',
				'ストーカー', '行為', 'の', '対象', 'に'
			]
		];

		$provider[] = [
			"公明執",
			[ '公明', '執' ]
		];

		$provider[] = [
			"IQテスト",
			[ 'IQ', 'テスト' ]
		];

		$provider[] = [
			"foo テスト bar",
			[ 'foo', 'テスト', 'bar' ]
		];

		$provider[] = [
			"foo テスト bar 123abc ^&'",
			[ 'foo', 'テスト', 'bar', '123abc', '^', '&', "'" ]
		];

		$provider[] = [
			"was discovered in 1957 and first sold as a medication in 1971",
			[
				'was', 'discovered', 'in', '1957', 'and',
				'first', 'sold', 'as', 'a', 'medication', 'in', '1971'
			]
		];

		// See JaTinySegmenterTokenizerTest for comparison
		$provider[] = [
			'日本語の新聞記事であれば文字単位で95%程度の精度で分かち書きが行えます。 ',
			[
				'日本語', 'の', '新聞', '記事', 'で',
				'あれ', 'ば', '文字', '単位',
				'で', '95', '%', '程度',
				'の', '精度', 'で', '分かち書き',
				'が', '行', 'え', 'ます', '。'
			]
		];

		return $provider;
	}

}
