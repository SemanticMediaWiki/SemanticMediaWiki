<?php

namespace Onoi\Tesa\Tests\Integration;

use Onoi\Tesa\StopwordAnalyzer;
use Onoi\Tesa\Sanitizer;

/**
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class SampleTextStopwordTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textByLanguageProvider
	 */
	public function testByLanguage( $languageCode, $text, $expected ) {

		$stopwordAnalyzer = new StopwordAnalyzer();
		$stopwordAnalyzer->loadListByLanguage( $languageCode );

		$sanitizer = new Sanitizer( $text );
		$sanitizer->toLowercase();

		$string = $sanitizer->sanitizeBy(
			$stopwordAnalyzer
		);

		$this->assertEquals(
			$expected,
			$string
		);
	}

	public function textByLanguageProvider() {

		// https://en.wikipedia.org/wiki/Stop_words
		$provider[] = array(
			'en',
			'In computing, stop words are words which are filtered out before or after processing of natural language data (text).[1] Though stop words usually refer to the most common words in a language, there is no single universal list of stop words used by all natural language processing tools, and indeed not all tools even use such a list. Some tools specifically avoid removing these stop words to support phrase search.',
			'computing stop words words filtered processing natural language data text 1 stop words refer common words language single universal list stop words natural language processing tools tools list tools specifically avoid removing stop words support phrase search'
		);

		// https://en.wikipedia.org/wiki/Query_expansion
		$provider[] = array(
			'en',
			"The goal of query expansion in this regard is by increasing recall, precision can potentially increase (rather than decrease as mathematically equated), by including in the result set pages which are more relevant (of higher quality), or at least equally relevant. Pages which would not be included in the result set, which have the potential to be more relevant to the user's desired query, are included, and without query expansion would not have, regardless of relevance.",
			"goal query expansion regard increasing recall precision potentially increase decrease mathematically equated including result set pages relevant quality equally relevant pages included result set potential relevant user's desired query included query expansion relevance"
		);

		return $provider;
	}

}
