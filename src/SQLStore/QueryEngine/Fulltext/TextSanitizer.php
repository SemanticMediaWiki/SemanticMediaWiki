<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Onoi\Tesa\Normalizer;
use Onoi\Tesa\Sanitizer;
use Onoi\Tesa\SanitizerFactory;
use Onoi\Tesa\Tokenizer\Tokenizer;
use Onoi\Tesa\Transliterator;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextSanitizer {

	/**
	 * @var SanitizerFactory
	 */
	private $sanitizerFactory;

	/**
	 * @var array
	 */
	private $languageDetection = [];

	/**
	 * @var integer
	 */
	private $minTokenSize = 3;

	/**
	 * @since 2.5
	 *
	 * @param SanitizerFactory $sanitizerFactory
	 */
	public function __construct( SanitizerFactory $sanitizerFactory ) {
		$this->sanitizerFactory = $sanitizerFactory;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getVersions() {

		$languageDetector = '(Disabled)';

		if ( isset( $this->languageDetection['TextCatLanguageDetector'] ) ) {
			$languageDetector = 'TextCatLanguageDetector (' . implode(', ', $this->languageDetection['TextCatLanguageDetector'] ) . ')';
		}

		return [
			'ICU (Intl) PHP-extension' => ( extension_loaded( 'intl' ) ? INTL_ICU_VERSION : '(Disabled)' ),
			'Tesa::Sanitizer'  => Sanitizer::VERSION,
			'Tesa::Transliterator' => Transliterator::VERSION,
			'Tesa::LanguageDetector' => $languageDetector
		];
	}

	/**
	 * @since 2.5
	 *
	 * @param array $languageDetection
	 */
	public function setLanguageDetection( array $languageDetection ) {
		$this->languageDetection = $languageDetection;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $minTokenSize
	 */
	public function setMinTokenSize( $minTokenSize ) {
		$this->minTokenSize = $minTokenSize;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 * @param boolean $isSearchTerm
	 *
	 * @return string
	 */
	public function sanitize( $text, $isSearchTerm = false ) {
		$start = microtime( true );
		$text = rawurldecode( trim( $text ) );

		$exemptionList = '';

		// Those have special meaning when running a match search against
		// the fulltext index (wildcard, phrase matching markers etc.)
		if ( $isSearchTerm ) {
			$exemptionList = [ '*', '"', '+', '-', '&', ',', '@', '~' ];
		}

		$sanitizer = $this->sanitizerFactory->newSanitizer( $text );
		$sanitizer->toLowercase();
		$sanitizer->applyTransliteration();
		$sanitizer->convertDoubleWidth();

		$sanitizer->replace(
			[ 'http://', 'https://', 'mailto:', '%2A', '_', '&#x005B;', '&#91;', "\n", "\t" ],
			[ '', '', '', '*', ' ', '[', '[', "", "" ]
		);

		$language = $this->predictLanguage( $text );

		$sanitizer->setOption(
			Sanitizer::WHITELIST,
			$exemptionList
		);

		$sanitizer->setOption(
			Sanitizer::MIN_LENGTH,
			$this->minTokenSize
		);

		$tokenizer = $this->sanitizerFactory->newPreferredTokenizerByLanguage(
			$text,
			$language
		);

		$tokenizer->setOption(
			Tokenizer::REGEX_EXEMPTION,
			$exemptionList
		);

		$text = $sanitizer->sanitizeWith(
			$tokenizer,
			$this->sanitizerFactory->newStopwordAnalyzerByLanguage( $language ),
			$this->sanitizerFactory->newSynonymizerByLanguage( $language )
		);

		// Remove possible spaces added by the tokenizer
		$text = str_replace(
			[ ' *', '* ', ' "', '" ', '+ ', '- ', '@ ', '~ ', '*+', '*-', '*~' ],
			[ '*', '*', '"', '"', '+', '-', '@', '~' ,'* +', '* -', '* ~' ],
			$text
		);

		//var_dump( $language, $text, (microtime( true ) - $start ) );
		return $text;
	}

	private function predictLanguage( $text ) {

		if ( $this->languageDetection === [] ) {
			return null;
		}

		$languageDetector = $this->sanitizerFactory->newNullLanguageDetector();

		if ( isset( $this->languageDetection['TextCatLanguageDetector'] ) ) {
			$languageDetector = $this->sanitizerFactory->newTextCatLanguageDetector();
			$languageDetector->setLanguageCandidates( $this->languageDetection['TextCatLanguageDetector'] );
		}

		return $languageDetector->detect(
			Normalizer::reduceLengthTo( $text, 200 )
		);
	}

}
