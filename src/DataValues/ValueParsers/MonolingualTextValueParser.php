<?php

namespace SMW\DataValues\ValueParsers;

use SMW\Localizer;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueParser implements ValueParser {

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $userValue
	 *
	 * @return array
	 */
	public function parse( $userValue ) {

		$text = $userValue;

		$languageCode = mb_substr( strrchr( $userValue, "@" ), 1 );

		// Remove the language code and marker from the text
		if ( $languageCode !== '' ) {
			$text = substr_replace( $userValue, '', ( mb_strlen( $languageCode ) + 1 ) * -1 );
		}

		return array( $text, Localizer::asBCP47FormattedLanguageCode( $languageCode ) );
	}

}
