<?php

namespace Onoi\Tesa;

use Onoi\Tesa\Tokenizer\Tokenizer;
use Onoi\Tesa\Synonymizer\Synonymizer;
use Onoi\Tesa\StopwordAnalyzer\StopwordAnalyzer;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class Sanitizer {

	const WHITELIST = 'WHITELIST';
	const MIN_LENGTH = 'MIN_LENGTH';

	/**
	 * Any change to the content of its data files should be reflected in a
	 * version change (the version number does not necessarily correlate with
	 * the library version)
	 */
	const VERSION = '0.2';

	/**
	 * @var string
	 */
	private $string = '';

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
	public function __construct( $string = '' ) {
		$this->setText( $string );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setOption( $name, $value ) {

		if ( $name === self::WHITELIST && is_array( $value ) && $value !== array() ) {
			$this->whiteList = array_fill_keys( $value, true );
		}

		if ( $name === self::MIN_LENGTH ) {
			$this->minLength = (int)$value;
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param string $string
	 */
	public function setText( $string ) {
		$this->string = $string;
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $flag
	 */
	public function applyTransliteration( $flag = Transliterator::DIACRITICS ) {
		$this->string = Normalizer::applyTransliteration( $this->string, $flag );
	}

	/**
	 * @see Localizer::convertDoubleWidth
	 *
	 * @since 0.1
	 *
	 * @param integer $flag
	 */
	public function convertDoubleWidth() {
		$this->string = Normalizer::convertDoubleWidth( $this->string );
	}

	/**
	 * @since 0.1
	 *
	 * @param Tokenizer $tokenizer
	 * @param StopwordAnalyzer $stopwordAnalyzer
	 *
	 * @return string
	 */
	public function sanitizeWith( Tokenizer $tokenizer, StopwordAnalyzer $stopwordAnalyzer, Synonymizer $synonymizer ) {

		// Treat non-words tokenizers (Ja,Zh*) differently
		$minLength = $tokenizer->isWordTokenizer() ? $this->minLength : 1;

		$words = $tokenizer->tokenize( $this->string );

		if ( !$words || !is_array( $words ) ) {
			return $this->string;
		}

		$index = array();
		$pos = 0;

		foreach ( $words as $key => $word ) {

			$word = $synonymizer->synonymize( $word );

			// If it is not an exemption and less than the required minimum length
			// or identified as stop word it is removed
			if ( !isset( $this->whiteList[$word] ) && ( mb_strlen( $word ) < $minLength || $stopwordAnalyzer->isStopWord( $word ) ) ) {
				continue;
			}

			// Simple proximity, check for same words appearing next to each other
			if ( isset( $index[$pos-1] ) && $index[$pos-1] === $word ) {
				continue;
			}

			$index[] = trim( $word );
			$pos++;
		}

		return implode( ' ' , $index );
	}

	/**
	 * @since 0.1
	 */
	public function toLowercase() {
		$this->string = Normalizer::toLowercase( $this->string );
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $length
	 */
	public function reduceLengthTo( $length ) {
		$this->string = Normalizer::reduceLengthTo( $this->string, $length );
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

}
