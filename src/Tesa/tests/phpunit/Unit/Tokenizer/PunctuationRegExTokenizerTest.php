<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\PunctuationRegExTokenizer;

/**
 * @covers \Onoi\Tesa\Tokenizer\PunctuationRegExTokenizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class PunctuationRegExTokenizerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\PunctuationRegExTokenizer',
			new PunctuationRegExTokenizer()
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $patternExemption, $expected ) {

		$instance = new PunctuationRegExTokenizer();

		$instance->setOption(
			PunctuationRegExTokenizer::REGEX_EXEMPTION,
			$patternExemption
		);

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);

		$this->assertTrue(
			$instance->isWordTokenizer()
		);
	}

	public function testisWordTokenizerFromInheritTokenizer() {

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'isWordTokenizer' )
			->will( $this->returnValue( false ) );

		$instance = new PunctuationRegExTokenizer( $tokenizer );

		$this->assertFalse(
			$instance->isWordTokenizer()
		);
	}

	public function testTokenizeWithOption() {

		$string = '123, 345';

		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tokenizer->expects( $this->once() )
			->method( 'setOption' );

		$tokenizer->expects( $this->once() )
			->method( 'tokenize' )
			->with( $this->equalTo( $string ) )
			->will( $this->returnValue( array( $string ) ) );

		$instance = new PunctuationRegExTokenizer( $tokenizer );

		$instance->setOption(
			PunctuationRegExTokenizer::REGEX_EXEMPTION,
			array( ',' )
		);

		$this->assertEquals(
			array( '123,', '345' ),
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {

		$provider[] = array(
			'123, 345^456&[foo:bar]',
			'',
			array( '123', '345', '456', 'foo', 'bar' )
		);

		$provider[] = array(
			'123, 345^456&[foo:bar]',
			array( ',', '&' ),
			array( '123,', '345', '456&', 'foo', 'bar' )
		);

		$provider[] = array(
			'123, 345^456&[foo:bar] 3.',
			array( ',', '&' ),
			array( '123,', '345', '456&', 'foo', 'bar', '3' )
		);

		return $provider;
	}

}
