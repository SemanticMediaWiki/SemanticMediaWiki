<?php

namespace Onoi\Tesa;

use Onoi\Tesa\StopwordAnalyzer\StopwordAnalyzer;
use Onoi\Tesa\StopwordAnalyzer\NullStopwordAnalyzer;
use Onoi\Tesa\StopwordAnalyzer\CdbStopwordAnalyzer;
use Onoi\Tesa\StopwordAnalyzer\ArrayStopwordAnalyzer;
use Onoi\Tesa\Synonymizer\Synonymizer;
use Onoi\Tesa\Synonymizer\NullSynonymizer;
use Onoi\Tesa\LanguageDetector\NullLanguageDetector;
use Onoi\Tesa\LanguageDetector\TextCatLanguageDetector;
use Onoi\Tesa\Tokenizer\CJKSimpleCharacterRegExTokenizer;
use Onoi\Tesa\Tokenizer\Tokenizer;
use Onoi\Tesa\Tokenizer\GenericRegExTokenizer;
use Onoi\Tesa\Tokenizer\JaCompoundGroupTokenizer;
use Onoi\Tesa\Tokenizer\IcuWordBoundaryTokenizer;
use Onoi\Tesa\Tokenizer\NGramTokenizer;
use Onoi\Tesa\Tokenizer\JaTinySegmenterTokenizer;
use Onoi\Tesa\Tokenizer\PunctuationRegExTokenizer;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class SanitizerFactory {

	/**
	 * @since 0.1
	 *
	 * @return Sanitizer
	 */
	public function newSanitizer( $text = '' ) {
		return new Sanitizer( $text );
	}

	/* StopwordAnalyzer */

	/**
	 * @since 0.1
	 *
	 * @param string|null $languageCode
	 *
	 * @return StopwordAnalyzer
	 */
	public function newStopwordAnalyzerByLanguage( $languageCode = null ) {

		if ( $languageCode === null ) {
			return $this->newNullStopwordAnalyzer();
		}

		$cdbStopwordAnalyzer = $this->newCdbStopwordAnalyzer(
			$languageCode
		);

		return $cdbStopwordAnalyzer->isAvailable() ? $cdbStopwordAnalyzer : $this->newNullStopwordAnalyzer();;
	}

	/**
	 * @since 0.1
	 *
	 * @return StopwordAnalyzer
	 */
	public function newCdbStopwordAnalyzer( $languageCode = null ) {
		return new CdbStopwordAnalyzer( CdbStopwordAnalyzer::getTargetByLanguage( $languageCode ) );
	}

	/**
	 * @since 0.1
	 *
	 * @param array $stopwords;
	 *
	 * @return StopwordAnalyzer
	 */
	public function newArrayStopwordAnalyzer( array $stopwords = array() ) {
		return new ArrayStopwordAnalyzer( $stopwords );
	}

	/**
	 * @since 0.1
	 *
	 * @return StopwordAnalyzer
	 */
	public function newNullStopwordAnalyzer() {
		return new NullStopwordAnalyzer();
	}

	/**
	 * @since 0.1
	 *
	 * @param string|null $languageCode
	 *
	 * @return Synonymizer
	 */
	public function newSynonymizerByLanguage( $languageCode = null ) {

		if ( $languageCode === null ) {
			return $this->newNullSynonymizer();
		}

		return $this->newNullSynonymizer();;
	}

	/* Synonymizer */

	/**
	 * @since 0.1
	 *
	 * @return Synonymizer
	 */
	public function newNullSynonymizer() {
		return new NullSynonymizer();
	}

	/* LanguageDetector */

	/**
	 * @since 0.1
	 *
	 * @return NullLanguageDetector
	 */
	public function newNullLanguageDetector() {
		return new NullLanguageDetector();
	}

	/**
	 * @since 0.1
	 *
	 * @return TextCatLanguageDetector
	 */
	public function newTextCatLanguageDetector() {
		return new TextCatLanguageDetector();
	}

	/* Tokenizer */

	/**
	 * @since 0.1
	 *
	 * @param string $text
	 * @param string|null $languageCode
	 *
	 * @return Tokenizer
	 */
	public function newPreferredTokenizerByLanguage( $text, $languageCode = null ) {

		$tokenizer = $this->newIcuWordBoundaryTokenizer();

		if ( !$tokenizer->isAvailable() && CharacterExaminer::contains( CharacterExaminer::CJK_UNIFIED, $text ) ) {
			return $this->newCJKMatchableTokenizer( $text );
		} elseif( !$tokenizer->isAvailable() ) {
			return $this->newGenericRegExTokenizer( $tokenizer );
		}

		$tokenizer->setLocale( $languageCode );

		$tokenizer->setWordTokenizerAttribute(
			!CharacterExaminer::contains( CharacterExaminer::CJK_UNIFIED, $text )
		);

		return $this->newGenericRegExTokenizer( $tokenizer );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $text
	 *
	 * @return Tokenizer
	 */
	public function newCJKMatchableTokenizer( $text ) {

		$tokenizer = null;

		if ( CharacterExaminer::contains( CharacterExaminer::HIRAGANA_KATAKANA, $text ) ) {
			$tokenizer = $this->newJaTinySegmenterTokenizer();
		} else {
			$tokenizer = $this->newNGramTokenizer( $tokenizer );
		}

		$tokenizer = $this->newCJKSimpleCharacterRegExTokenizer( $tokenizer );

		return $this->newGenericRegExTokenizer( $tokenizer );
	}

	/**
	 * @since 0.1
	 *
	 * @param Tokenizer|null $tokenizer
	 *
	 * @return Tokenizer
	 */
	public function newIcuWordBoundaryTokenizer( Tokenizer $tokenizer = null ) {
		return new IcuWordBoundaryTokenizer( $tokenizer );
	}

	/**
	 * @since 0.1
	 *
	 * @param Tokenizer|null $tokenizer
	 *
	 * @return Tokenizer
	 */
	public function newGenericRegExTokenizer( Tokenizer $tokenizer = null ) {
		return new GenericRegExTokenizer( $tokenizer );
	}

	/**
	 * @since 0.1
	 *
	 * @param Tokenizer|null $tokenizer
	 *
	 * @return Tokenizer
	 */
	public function newPunctuationRegExTokenizer( Tokenizer $tokenizer = null ) {
		return new PunctuationRegExTokenizer( $tokenizer );
	}

	/**
	 * @since 0.1
	 *
	 * @return Tokenizer
	 */
	public function newJaCompoundGroupTokenizer( Tokenizer $tokinizer = null ) {
		return new JaCompoundGroupTokenizer( $tokinizer );
	}

	/**
	 * @since 0.1
	 *
	 * @return Tokenizer
	 */
	public function newJaTinySegmenterTokenizer( Tokenizer $tokinizer = null ) {
		return new JaTinySegmenterTokenizer( $tokinizer );
	}

	/**
	 * @since 0.1
	 *
	 * @return Tokenizer
	 */
	public function newCJKSimpleCharacterRegExTokenizer( Tokenizer $tokinizer = null ) {
		return new CJKSimpleCharacterRegExTokenizer( $tokinizer );
	}

	/**
	 * @since 0.1
	 *
	 * @return Tokenizer
	 */
	public function newNGramTokenizer( Tokenizer $tokinizer = null, $ngram = 2 ) {
		return new NGramTokenizer( $tokinizer, $ngram );
	}

}
