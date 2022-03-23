<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Normalizer;

/**
 * @covers \Onoi\Tesa\Normalizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NormalizerTest extends \PHPUnit_Framework_TestCase {

	public function testTransliteration() {

		$this->assertEquals(
			'AAAAAEAaaaaaeaOOOOOOEOoooooeoEEEEeeeeðCcÐIIIIiiiiUUUUEuuuueNnSsYyyZz',
			Normalizer::applyTransliteration( 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž' )
		);
	}

	public function testConvertDoubleWidth() {

		$this->assertEquals(
			'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
			Normalizer::convertDoubleWidth( '０１２３４５６７８９ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ' )
		);
	}

	public function testReduceLengthTo() {

		$this->assertEquals(
			'ABC',
			Normalizer::reduceLengthTo( 'ABCDEF', 3 )
		);

		$this->assertEquals(
			'ABCDEF',
			Normalizer::reduceLengthTo( 'ABCDEF' )
		);

		$this->assertEquals(
			'ABCD',
			Normalizer::reduceLengthTo( 'ABCD EF', 4 )
		);

		$this->assertEquals(
			'ABC',
			Normalizer::reduceLengthTo( 'ABC D EF', 4 )
		);

		$this->assertEquals(
			'ABCD',
			Normalizer::reduceLengthTo( 'ABCD EF', 5 )
		);

		$this->assertEquals(
			'abc def gh',
			Normalizer::reduceLengthTo( 'abc def gh in 123', 12 )
		);
	}

	public function testToLowercase() {

		$this->assertEquals(
			'abcdef',
			Normalizer::toLowercase( 'ABCDEF' )
		);
	}

}
