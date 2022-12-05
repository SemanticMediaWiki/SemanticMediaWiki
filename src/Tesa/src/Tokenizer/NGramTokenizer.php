<?php

namespace Onoi\Tesa\Tokenizer;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NGramTokenizer implements Tokenizer {

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @var integer
	 */
	private $ngramSize = 2;

	/**
	 * @var boolean
	 */
	private $withMarker = false;

	/**
	 * @since 0.1
	 *
	 * @param Tokenizer $tokenizer
	 * @param integer $ngramSize
	 */
	public function __construct( Tokenizer $tokenizer = null, $ngramSize = 2 ) {
		$this->tokenizer = $tokenizer;
		$this->ngramSize = (int)$ngramSize;
	}

	/**
	 * @since 0.1
	 *
	 * @param boolean $withMarker
	 */
	public function withMarker( $withMarker ) {
		$this->withMarker = (bool)$withMarker;
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $ngramSize
	 */
	public function setNgramSize( $ngramSize ) {
		$this->ngramSize = (int)$ngramSize;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function setOption( $name, $value ) {
		if ( $this->tokenizer !== null ) {
			$this->tokenizer->setOption( $name, $value );
		}
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function isWordTokenizer() {
		return false;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function tokenize( $string ) {

		if ( $this->tokenizer !== null ) {
			$string = implode( " ", $this->tokenizer->tokenize( $string ) );
		}

		$result = $this->createNGrams( $string, $this->ngramSize, $this->withMarker );

		if ( $result !== false ) {
			return $result;
		}

		return array();
	}

	private function createNGrams( $text, $ngramSize, $withMarker ) {

		$ngramList = array();

		// Identify the beginning-of-word and end-of-word
		if ( $withMarker ) {
			$text = '_' . str_replace( ' ', '_', $text );
		}

		$text = mb_strtolower( $text );
		$textLength = mb_strlen( $text, 'UTF-8' );

		for ( $i = 0; $i < $textLength; ++$i ) {

			// Those failing the length of the ngramSize are skipped
			if ( !$withMarker && $i + $ngramSize > $textLength ) {
				continue;
			}

			$ngram = mb_substr( $text, $i, $ngramSize, 'UTF-8' );

			// str_pad has issues with utf-8 length
			if ( $withMarker && ( $ngl = mb_strlen( $ngram, 'UTF-8' ) ) < $ngramSize ) {
				$ngram = $ngram . str_repeat( '_', ( $ngramSize - $ngl ) );
			}

			$ngramList[] = $ngram;
		}

		return array_values( $ngramList );
	}

}
