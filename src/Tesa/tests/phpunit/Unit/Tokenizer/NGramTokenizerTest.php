<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\NGramTokenizer;

/**
 * @covers \Onoi\Tesa\Tokenizer\NGramTokenizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NGramTokenizerTest extends \PHPUnit_Framework_TestCase {

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

		if ( version_compare( phpversion(), '5.4', '<' ) ) {
			$this->markTestSkipped(
				"Ehh, PHP 5.3 returns with unexpected results"
			);
		}

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

		$expected = array(
			'_tex',
			'text',
			'ext_',
			'xt__',
			't___'
		);

		$instance = new NGramTokenizer( null, 4 );
		$instance->withMarker( true );

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function testTokenizeWithStartEndMarker2() {

		$string = '教授は';

		$expected = array(
			'_教授',
			'教授は',
			'授は_',
			'は__'
		);

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
			->with( $this->equalTo( $string ) )
			->will( $this->returnValue( array( $string ) ) );

		$instance = new NGramTokenizer( $tokenizer );

		$instance->setOption(
			NGramTokenizer::REGEX_EXEMPTION,
			array( 'Foo' )
		);

		$this->assertEquals(
			array( '红色', '色中', '中华' ),
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {

		$provider[] = array(
			'TEXT',
			'4',
			array(
				'text'
			)
		);

		$provider[] = array(
			'12345678',
			'2',
			array(
				'12',
				'23',
				'34',
				'45',
				'56',
				'67',
				'78'
			)
		);

		$provider[] = array(
			'12345678',
			'3',
			array(
				'123',
				'234',
				'345',
				'456',
				'567',
				'678'
			)
		);

		$provider[] = array(
			'hello',
			'3',
			array(
				'hel',
				'ell',
				'llo'
			)
		);

		$provider[] = array(
			'Hello World!',
			'3',
			array(
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
			)
		);

		$provider[] = array(
			'Новости',
			'3',
			array(
				'нов',
				'ово',
				'вос',
				'ост',
				'сти'
			)
		);

		$provider[] = array(
			'1時36分更新',
			'3',
			array(
				'1時3',
				'時36',
				'36分',
				'6分更',
				'分更新'
			)
		);

		$provider[] = array(
			'こんにちは世界！',
			'2',
			array(
				'こん',
				'んに',
				'にち',
				'ちは',
				'は世',
				'世界',
				'界！'
			)
		);

		return $provider;
	}

}
