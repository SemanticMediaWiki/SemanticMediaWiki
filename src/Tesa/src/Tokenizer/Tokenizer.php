<?php

namespace Onoi\Tesa\Tokenizer;

/**
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
interface Tokenizer {

	/**
	 * Under some circumstances (used as search term) specific characters should
	 * be exempted from the regular expression.
	 */
	const REGEX_EXEMPTION = 'regex.exemption';

	/**
	 * @since 0.1
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setOption( $name, $value );

	/**
	 * Some simple tokenizer may not rely on whitespaces to build
	 * tokens.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function isWordTokenizer();

	/**
	 * @since 0.1
	 *
	 * @param string $string
	 *
	 * @return array|false
	 */
	public function tokenize( $string );

}
