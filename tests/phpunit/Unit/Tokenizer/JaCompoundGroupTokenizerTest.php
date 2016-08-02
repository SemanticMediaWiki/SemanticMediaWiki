<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\JaCompoundGroupTokenizer;

/**
 * @covers \Onoi\Tesa\Tokenizer\JaCompoundGroupTokenizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class JaCompoundGroupTokenizerTest extends \PHPUnit_Framework_TestCase {

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

		if ( version_compare( phpversion(), '5.4', '<' ) ) {
			$this->markTestSkipped(
				"Boo, PHP 5.3 returns with unexpected results"
			);
		}

		$instance = new JaCompoundGroupTokenizer();

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function testTokenizeWithOption() {

		if ( version_compare( phpversion(), '5.4', '<' ) ) {
			$this->markTestSkipped(
				"Ehh, PHP 5.3 returns with unexpected results"
			);
		}

		$string = 'と歓声を上げていました';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'setOption' );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $this->equalTo( $string ) )
			->will( $this->returnValue( array( $string ) ) );

		$instance = new JaCompoundGroupTokenizer( $tokenizer );

		$instance->setOption(
			JaCompoundGroupTokenizer::REGEX_EXEMPTION,
			array( 'Foo' )
		);

		$this->assertEquals(
			array( '歓声', '上' ),
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {

		$provider[] = array(
			'と歓声を上げていました。 十勝農業改良普及センターによりますと',
			array( '歓声', '上', '十勝農業改良普及', 'センター' )
		);

		return $provider;
	}

}
