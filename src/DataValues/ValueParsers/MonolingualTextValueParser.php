<?php

namespace SMW\DataValues\ValueParsers;

use SMW\Localizer;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 * @reviewer thomas-topway-it
 */
class MonolingualTextValueParser implements ValueParser {

	/**
	 * @var array
	 */
	private $errors = [];

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
	 * @param string|array $userValue
	 *
	 * @return array
	 */
	public function parse( $userValue ) {
		// Allow things like [ "en" => "Foo ..." ] when retrieved from a JSON string
		if ( is_array( $userValue ) ) {
			foreach ( $userValue as $key => $value ) {
				$languageCode = is_string( $key ) ? $key : '';
				$text = is_string( $value ) ? $value : '';
			}
		} else {
			$text = $userValue;
			$languageCode = mb_substr( strrchr( $userValue, "@" ), 1 );

			// Remove the language code and marker from the text
			if ( $languageCode !== '' ) {
				$text = substr_replace( $userValue, '', ( mb_strlen( $languageCode ) + 1 ) * -1 );
			}
		}

		$languageCode = Localizer::asBCP47FormattedLanguageCode( $languageCode );
		$nonstandardLanguageCodeMapping = \LanguageCode::getNonstandardLanguageCodeMapping();

		$mappedLanguageCode = array_search( $languageCode, $nonstandardLanguageCodeMapping ) ?: $languageCode;

		return [ $text, $mappedLanguageCode ];
	}

}
