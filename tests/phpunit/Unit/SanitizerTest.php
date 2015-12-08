<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Sanitizer;
use Onoi\Tesa\Tokenizer;
use Onoi\Tesa\StopwordAnalyzer;

/**
 * @covers \Onoi\Tesa\Sanitizer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class SanitizerTest extends \PHPUnit_Framework_TestCase {

	public function testTransliteration() {

		$instance = new Sanitizer( 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž' );
		$instance->applyTransliteration();

		$this->assertEquals(
			'AAAAAEAaaaaaeaOOOOOOEOoooooeoEEEEeeeeðCcÐIIIIiiiiUUUUEuuuueNnSsYyyZz',
			$instance
		);
	}

	public function testToLowercase() {

		$instance = new Sanitizer( 'ÀÁÂÃÄÅ ABC 텍스트의 テスト часто הוא פשוט' );
		$instance->toLowercase();

		$this->assertEquals(
			'àáâãäå abc 텍스트의 テスト часто הוא פשוט',
			$instance
		);
	}

	public function testReduceLengthTo() {

		$instance = new Sanitizer( 'ABCDEF' );
		$instance->reduceLengthTo( 3 );

		$this->assertEquals(
			3,
			mb_strlen( $instance )
		);

		$instance->reduceLengthTo( 10 );

		$this->assertEquals(
			3,
			mb_strlen( $instance )
		);
	}

	public function testReduceLengthToNearestWholeWordForLatinString() {

		$instance = new Sanitizer( 'abc def gh in 123' );
		$instance->reduceLengthTo( 12 );

		$this->assertEquals(
			10,
			mb_strlen( $instance )
		);

		$this->assertEquals(
			'abc def gh',
			$instance
		);
	}

	public function testReduceLengthToNearestWholeWordForNonLatinString() {

		if ( version_compare( phpversion(), '5.4', '<' ) ) {
			$this->markTestSkipped(
				"Boo, PHP 5.3 returns with `Failed asserting that 9 matches expected 3`"
			);
		}

		$instance = new Sanitizer( '一　二　三' );
		$instance->reduceLengthTo( 3 );

		$this->assertEquals(
			3,
			mb_strlen( $instance )
		);

		$this->assertEquals(
			'一　二',
			$instance
		);
	}

	public function testSanitizeByStopwords() {

		$instance = new Sanitizer( 'Foo bar foobar' );

		$stopwordAnalyzer = new StopwordAnalyzer();
		$stopwordAnalyzer->setCustomStopwordList( array( 'zh' => array( 'bar' ) ) );

		$this->assertEquals(
			'Foo foobar',
			$instance->sanitizeBy( $stopwordAnalyzer )
		);
	}

	public function testSanitizeByStopwordsToIncludeExemptionWithMinLengthRestriction() {

		$instance = new Sanitizer( 'Foo bar foobar' );

		$instance->setOption( ONOI_TESA_CHARACTER_MIN_LENGTH, 4 );
		$instance->setOption( ONOI_TESA_WORD_WHITELIST, array( 'bar' ) );

		$stopwordAnalyzer = new StopwordAnalyzer();
		$stopwordAnalyzer->setCustomStopwordList( array( 'zh' => array( 'bar' ) ) );

		$this->assertEquals(
			'bar foobar',
			$instance->sanitizeBy( $stopwordAnalyzer )
		);
	}

	public function testTrySanitizeByStopwordsForNoAvailableToken() {

		$instance = new Sanitizer( '' );

		$stopwordAnalyzer = new StopwordAnalyzer();

		$this->assertEquals(
			'',
			$instance->sanitizeBy( $stopwordAnalyzer )
		);
	}

	public function testTrySanitizeByStopwordsWithProximityCheck() {

		$instance = new Sanitizer( 'foo foo テスト テスト' );

		$stopwordAnalyzer = new StopwordAnalyzer();

		$this->assertEquals(
			'foo テスト',
			$instance->sanitizeBy( $stopwordAnalyzer )
		);
	}

	public function testReplace() {

		$instance = new Sanitizer( 'テスト' );
		$instance->replace( array( 'テスト' ), array( 'Test' ) );

		$this->assertEquals(
			'Test',
			$instance
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testGetTokens( $string, $flag, $expected ) {

		$instance = new Sanitizer( $string );

		$this->assertEquals(
			$expected,
			$instance->getTokens( $flag )
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
			'Abc def',
			null,
			false
		);

		return $provider;
	}

}
