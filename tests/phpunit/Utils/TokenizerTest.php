<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Tokenizer;

/**
 * @covers \SMW\Utils\Tokenizer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TokenizerTest extends \PHPUnit\Framework\TestCase {

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
		$provider[] = [
			'foo',
			[ 'foo' ]
		];

		$provider[] = [
			'foo bar',
			[ 'foo', 'bar' ]
		];

		return $provider;
	}

}
