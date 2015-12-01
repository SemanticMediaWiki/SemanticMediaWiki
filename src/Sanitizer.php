<?php

namespace Onoi\Tesa;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class Sanitizer {

	/**
	 * @var string
	 */
	private $string = '';

	/**
	 * @var null
	 */
	private $encoding = null;

	/**
	 * @since 0.1
	 *
	 * @param string $string
	 */
	public function __construct( $string ) {
		$this->string = $string;
		$this->encoding = $this->detectEncoding( $string );
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $flag
	 */
	public function applyTransliteration( $flag = Transliterator::DIACRITICS ) {
		$this->string = Transliterator::transliterate( $this->string, $flag );
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $flag
	 *
	 * @return array
	 */
	public function getTokens( $flag = Tokenizer::STRICT ) {
		return Tokenizer::tokenize( $this->string, $flag );
	}

	/**
	 * @since 0.1
	 *
	 * @param StopwordAnalyzer $stopwordAnalyzer
	 *
	 * @return string
	 */
	public function sanitizeBy( StopwordAnalyzer $stopwordAnalyzer ) {

		$words = $this->getTokens();
		$index = array();

		if ( !$words || !is_array( $words ) ) {
			return $this->string;
		}

		foreach ( $words as $word ) {

			if ( $stopwordAnalyzer->isStopWord( $word ) ) {
				continue;
			}

			$index[] = $word;
		}

		return implode( ' ' , $index );
	}

	/**
	 * @since 0.1
	 */
	public function toLowercase() {
		$this->string = mb_strtolower( $this->string, $this->encoding );
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $length
	 */
	public function reduceLengthTo( $length ) {

		if ( mb_strlen( $this->string ) <= $length ) {
			return;
		}

		if ( strpos( $this->string, ' ' ) !== false ) {
			$length = strrpos( mb_substr( $this->string, 0, $length, $this->encoding ), ' ' ); // last whole word
		}

		$this->string = mb_substr( $this->string, 0, $length, $this->encoding );
	}

	/**
	 * @see http://www.phpwact.org/php/i18n/utf-8#str_replace
	 * @since 0.1
	 *
	 * @param string $search
	 * @param string $replace
	 */
	public function replace( $search, $replace ) {
		$this->string = str_replace( $search, $replace, $this->string );
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function containsJapaneseCharacters() {
		return preg_match('/[\x{3040}-\x{309F}]/u', $this->string ) > 0 || preg_match('/[\x{30A0}-\x{30FF}]/u', $this->string ) > 0; // isHiragana || isKatakana
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function containsKoreanCharacters() {
		return preg_match('/[\x{3130}-\x{318F}]/u', $this->string ) > 0 || preg_match('/[\x{AC00}-\x{D7AF}]/u', $this->string ) > 0;
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function containsChineseCharacters() {
		return preg_match('/[\x{4e00}-\x{9fa5}]/u', $this->string ) > 0;
	}

	/**
	 * @since 0.1
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->string;
	}

	private function detectEncoding( $string) {
		return mb_detect_encoding( $string );
	}

}
