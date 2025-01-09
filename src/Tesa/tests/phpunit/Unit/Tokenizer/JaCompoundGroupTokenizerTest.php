<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\JaCompoundGroupTokenizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\Tokenizer\JaCompoundGroupTokenizer
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class JaCompoundGroupTokenizerTest extends TestCase {

	public function testUnknownOption() {
		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\JaCompoundGroupTokenizer',
			new JaCompoundGroupTokenizer()
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $expected ) {
		$instance = new JaCompoundGroupTokenizer();

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function testTokenizeWithOption() {
		$string = 'と歓声を上げていました';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'setOption' );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $string )
			->willReturn( [ $string ] );

		$instance = new JaCompoundGroupTokenizer( $tokenizer );

		$instance->setOption(
			JaCompoundGroupTokenizer::REGEX_EXEMPTION,
			[ 'Foo' ]
		);

		$this->assertEquals(
			[ '歓声', '上' ],
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {
		$provider[] = [
			'と歓声を上げていました。 十勝農業改良普及センターによりますと',
			[ '歓声', '上', '十勝農業改良普及', 'センター' ]
		];

		return $provider;
	}

}
