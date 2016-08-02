<?php

namespace Onoi\Tesa\Tokenizer;

use IntlRuleBasedBreakIterator;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class IcuWordBoundaryTokenizer implements Tokenizer {

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @var string
	 */
	private $locale = 'en';

	/**
	 * @var string
	 */
	private $isWordTokenizer = true;

	/**
	 * @since 0.1
	 *
	 * @param Tokenizer|null $tokenizer
	 */
	public function __construct( Tokenizer $tokenizer = null ) {
		$this->tokenizer = $tokenizer;
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
		return $this->isWordTokenizer;
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function setWordTokenizerAttribute( $usesWordBoundaries ) {
		return $this->isWordTokenizer = $usesWordBoundaries;
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isAvailable() {
		return class_exists( 'IntlRuleBasedBreakIterator' );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $locale
	 */
	public function setLocale( $locale ) {
		$this->locale = $locale;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $string
	 *
	 * @return array|false
	 */
	public function tokenize( $string ) {

		if ( $this->tokenizer !== null ) {
			$string = implode( " ", $this->tokenizer->tokenize( $string ) );
		}

		if ( !$this->isAvailable() ) {
			return $this->tokenizer !== null ? $this->tokenizer->tokenize( $string ) : array( $string );
		}

		return $this->createTokens( $string );
	}

	private function createTokens( $string ) {

		$tokens = array();

		if ( $tokenizer = IntlRuleBasedBreakIterator::createWordInstance( $this->locale ) ) {
			$tokenizer->setText( $string );
			$prev = 0;

			foreach ( $tokenizer as $token ) {

				if ( $token == 0 ) {
					continue;
				}

				$res = substr( $string, $prev, $token - $prev );

				if ( $res !== '' && $res !== ' ' ) {
					$tokens[] = $res;
				}

				$prev = $token;
			}
		}

		return $tokens;
	}

}
