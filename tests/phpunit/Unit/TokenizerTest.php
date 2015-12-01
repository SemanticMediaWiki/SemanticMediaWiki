<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer;

/**
 * @covers \Onoi\Tesa\Tokenizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class TokenizerTest extends \PHPUnit_Framework_TestCase {

	public function testUnknownOption() {

		$this->assertFalse(
			Tokenizer::tokenize( 'foo', null )
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $flag, $expected ) {

		$this->assertEquals(
			$expected,
			Tokenizer::tokenize( $string, $flag )
		);
	}

	public function stringProvider() {

		$provider[] = array(
			'A test string (that has no);deep meaning',
			Tokenizer::LAZY,
			array( 'A', 'test', 'string', '(that', 'has', 'no);deep', 'meaning' )
		);

		$provider[] = array(
			'A test string (that has no);deep meaning',
			Tokenizer::STRICT,
			array( 'A', 'test', 'string', 'that', 'has', 'no' , 'deep', 'meaning' )
		);

		$provider[] = array(
			'Further_tests:shows.a[real] nice?! flower-shop',
			Tokenizer::LAZY,
			array( 'Further_tests:shows.a[real]', 'nice?!', 'flower-shop' )
		);

		$provider[] = array(
			'Further_tests:shows.a[real] nice?! flower-shop',
			Tokenizer::STRICT,
			array( 'Further', 'tests', 'shows', 'a', 'real', 'nice' , 'flower', 'shop' )
		);

		return $provider;
	}

}
