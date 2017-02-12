<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Tokenizer;

/**
 * @covers \SMW\Utils\Tokenizer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TokenizerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textProvider
	 */
	public function testTokenize( $text, $expected ) {

		$this->assertEquals(
			$expected,
			Tokenizer::tokenize( $text )
		);
	}

	public function textProvider() {

		$provider[] = array(
			'foo',
			array( 'foo' )
		);

		$provider[] = array(
			'foo bar',
			array( 'foo', 'bar' )
		);

		return $provider;
	}

}
