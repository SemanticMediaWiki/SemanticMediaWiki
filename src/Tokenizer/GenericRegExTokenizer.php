<?php

namespace Onoi\Tesa\Tokenizer;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class GenericRegExTokenizer implements Tokenizer {

	/**
	 * @var Tokenizer
	 */
	private $tokenizer;

	/**
	 * @var string
	 */
	private $patternExemption = '';

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

		if ( $name === self::REGEX_EXEMPTION ) {
			$this->patternExemption = $value;
		}
	}

	/**
	 * @since 0.1
	 *
	 * {@inheritDoc}
	 */
	public function isWordTokenizer() {
		return $this->tokenizer !== null ? $this->tokenizer->isWordTokenizer() :true;
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

		// (?<=\p{L})(?=\p{N}) to split alphanumeric and numeric

		$pattern = str_replace(
			$this->patternExemption,
			'',
			'([\s\-_,:;?!%\'\|\/\(\)\[\]{}<>\r\n"]|(?<!\d)\.(?!\d)|(?<=\p{L})(?=\p{N}))'
		);

		$result = preg_split( '/' . $pattern . '/u', $string, null, PREG_SPLIT_NO_EMPTY );

		if ( $result === false ) {
			$result = array();
		}

		return $result;
	}

}
