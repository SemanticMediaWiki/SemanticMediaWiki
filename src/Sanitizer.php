<?php

namespace Onoi\Tesa;

/**
 * @since 0.1
 *
 * @{
 */
// @codeCoverageIgnoreStart
define( 'ONOI_TESA_CHARACTER_MIN_LENGTH', 'ONOI_TESA_CHARACTER_MIN_LENGTH' );
define( 'ONOI_TESA_WORD_WHITELIST', 'ONOI_TESA_WORD_WHITELIST' );
// @codeCoverageIgnoreEnd
/**@}
 */

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class Sanitizer {

	/**
	 * Any change to the content of its data files should be reflected in a
	 * version change (the version number does not necessarily correlate with
	 * the library version)
	 */
	const VERSION = '0.1.1';

	/**
	 * @var string
	 */
	private $string = '';

	/**
	 * @var null|string
	 */
	private $encoding = null;

	/**
	 * @var array
	 */
	private $whiteList = array();

	/**
	 * @var array
	 */
	private $minLength = 3;

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
	 * @since 1.0
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setOption( $name, $value ) {

		if ( $name === ONOI_TESA_WORD_WHITELIST && $value !== array() ) {
			$this->whiteList = array_fill_keys( $value, true );
		}

		if ( $name === ONOI_TESA_CHARACTER_MIN_LENGTH ) {
			$this->minLength = (int)$value;
		}
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

		if ( !$words || !is_array( $words ) ) {
			return $this->string;
		}

		$index = array();
		$pos = 0;

		foreach ( $words as $key => $word ) {

			// If it is not an exemption and less than the required minimum length
			// or identified as stop word it is removed
			if ( !isset( $this->whiteList[$word] ) && ( mb_strlen( $word ) < $this->minLength || $stopwordAnalyzer->isStopWord( $word ) ) ) {
				continue;
			}

			// Simple proximity, check for same words appearing next to each other
			if ( isset( $index[$pos-1] ) && $index[$pos-1] === $word ) {
				continue;
			}

			$index[] = $word;
			$pos++;
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
	 * @return string
	 */
	public function __toString() {
		return $this->string;
	}

	private function detectEncoding( $string) {
		return mb_detect_encoding( $string );
	}

}
