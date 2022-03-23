<?php

namespace Onoi\Tesa\Tests\Integration;

use Onoi\Tesa\SanitizerFactory;
use Onoi\Tesa\Tokenizer\NGramTokenizer;

/**
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CombinedNGramTokenizerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textProvider
	 */
	public function testNgramWithBeginEndMarker( $languageCode, $ngramSize, $text, $expected ) {

		$sanitizerFactory = new SanitizerFactory();

		$tokenizer = $sanitizerFactory->newNGramTokenizer(
			$sanitizerFactory->newGenericRegExTokenizer()
		);

		$tokenizer->withMarker( true );
		$tokenizer->setNgramSize( $ngramSize );

		$tokens = $tokenizer->tokenize( $text );

		$this->assertEquals(
			$expected,
			$tokens
		);
	}

	public function textProvider() {

		// https://en.wikipedia.org/wiki/Stop_words
		$provider[] = array(
			'en',
			'2',
			//
			'In computing, stop words are words which are filtered ...',
			//
			array(
				0 => '_i',
				1 => 'in',
				2 => 'n_',
				3 => '_c',
				4 => 'co',
				5 => 'om',
				6 => 'mp',
				7 => 'pu',
				8 => 'ut',
				9 => 'ti',
				10 => 'in',
				11 => 'ng',
				12 => 'g_',
				13 => '_s',
				14 => 'st',
				15 => 'to',
				16 => 'op',
				17 => 'p_',
				18 => '_w',
				19 => 'wo',
				20 => 'or',
				21 => 'rd',
				22 => 'ds',
				23 => 's_',
				24 => '_a',
				25 => 'ar',
				26 => 're',
				27 => 'e_',
				28 => '_w',
				29 => 'wo',
				30 => 'or',
				31 => 'rd',
				32 => 'ds',
				33 => 's_',
				34 => '_w',
				35 => 'wh',
				36 => 'hi',
				37 => 'ic',
				38 => 'ch',
				39 => 'h_',
				40 => '_a',
				41 => 'ar',
				42 => 're',
				43 => 'e_',
				44 => '_f',
				45 => 'fi',
				46 => 'il',
				47 => 'lt',
				48 => 'te',
				49 => 'er',
				50 => 're',
				51 => 'ed',
				52 => 'd_',
			)
		);

		return $provider;
	}

}
