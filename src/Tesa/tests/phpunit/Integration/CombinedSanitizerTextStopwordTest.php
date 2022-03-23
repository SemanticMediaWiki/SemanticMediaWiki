<?php

namespace Onoi\Tesa\Tests\Integration;

use Onoi\Tesa\SanitizerFactory;

/**
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CombinedSanitizerTextStopwordTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textByLanguageProvider
	 */
	public function testByLanguage( $languageCode, $text, $expected ) {

		$sanitizerFactory = new SanitizerFactory();

		$sanitizer = $sanitizerFactory->newSanitizer( $text );
		$sanitizer->toLowercase();

		$text = $sanitizer->sanitizeWith(
			$sanitizerFactory->newGenericRegExTokenizer(),
			$sanitizerFactory->newCdbStopwordAnalyzer( $languageCode ),
			$sanitizerFactory->newNullSynonymizer()
		);

		$this->assertEquals(
			$expected,
			$text
		);
	}

	public function textByLanguageProvider() {

		// https://en.wikipedia.org/wiki/Stop_words
		$provider[] = array(
			'en',
			//
			'In computing, stop words are words which are filtered out before or after processing of ' .
			'natural language data (text).[1] Though stop words usually refer to the most common words ' .
			'in a language, there is no single universal list of stop words used by all natural language ' .
			'processing tools, and indeed not all tools even use such a list. Some tools specifically avoid '.
			'removing these stop words to support phrase search.',
			//
			'computing stop words filtered processing natural language data text stop words refer common ' .
			'words language single universal list stop words natural language processing tools list tools ' .
			'specifically avoid removing stop words support phrase search'
		);

		// https://es.wikipedia.org/wiki/Palabra_vac%C3%ADa
		$provider[] = array(
			'es',
			//
			'Palabras vacías es el nombre que reciben las palabras sin significado como artículos, pronombres, ' .
			'preposiciones, etc. que son filtradas antes o después del procesamiento de datos en lenguaje natural ' .
			'(texto). A Hans Peter Luhn, uno de los pioneros en recuperación de información, se le atribuye la ' .
			'acuñación de la locución inglesa stop words y el uso del concepto en su diseño. Está controlada por ' .
			'introducción humana y no automática.',
			//
			'palabras vacías nombre que reciben palabras significado artículos pronombres preposiciones etc que ' .
			'son filtradas después del procesamiento datos lenguaje natural texto hans peter luhn pioneros ' .
			'recuperación información atribuye acuñación locución inglesa stop words del concepto diseño está ' .
			'controlada introducción humana automática'
		);

		// https://de.wikipedia.org/wiki/Stoppwort
		$provider[] = array(
			'de',
			//
			'Stoppwörter nennt man im Information Retrieval Wörter, die bei einer Volltextindexierung nicht beachtet ' .
			'werden, da sie sehr häufig auftreten und gewöhnlich keine Relevanz für die Erfassung des Dokumentinhalts ' .
			'besitzen.',
			//
			'stoppwörter nennt information retrieval wörter volltextindexierung beachtet häufig auftreten gewöhnlich ' .
			'relevanz erfassung dokumentinhalts besitzen'
		);

		// https://en.wikipedia.org/wiki/Query_expansion
		$provider[] = array(
			'en',
			//
			"The goal of query expansion in this regard is by increasing recall, precision can potentially increase " .
			"(rather than decrease as mathematically equated), by including in the result set pages which are more " .
			"relevant (of higher quality), or at least equally relevant. Pages which would not be included in the " .
			"result set, which have the potential to be more relevant to the user's desired query, are included, and " .
			"without query expansion would not have, regardless of relevance.",
			//
			"goal query expansion regard increasing recall precision potentially increase decrease mathematically " .
			"equated including result set pages relevant quality equally relevant pages included result set potential " .
			"relevant user desired query included query expansion relevance"
		);

		return $provider;
	}

}
