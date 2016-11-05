<?php

namespace SMW\ExtraneousLanguage;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageFallbackFinder {

	/**
	 * @var LanguageJsonFileContentsReader
	 */
	private $languageJsonFileContentsReader;

	/**
	 * @var string
	 */
	private $canonicalFallbackLanguageCode = 'en';

	/**
	 * @var array
	 */
	private $fallbackLanguages = array();

	/**
	 * @since 2.5
	 *
	 * @param LanguageJsonFileContentsReader $LanguageJsonFileContentsReader
	 */
	public function __construct( LanguageJsonFileContentsReader $languageJsonFileContentsReader ) {
		$this->languageJsonFileContentsReader = $languageJsonFileContentsReader;
	}

	/**
	 * @since 2.5
	 */
	public function emptyByLanguageCode( $languageCode ) {
		unset( $this->fallbackLanguages[strtolower( trim( $languageCode ) )] );
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getCanonicalFallbackLanguageCode() {
		return $this->canonicalFallbackLanguageCode;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getFallbackLanguageBy( $languageCode = '' ) {

		$languageCode = strtolower( trim( $languageCode ) );

		if ( isset( $this->fallbackLanguages[$languageCode] ) ) {
			return $this->fallbackLanguages[$languageCode];
		}

		$index = 'fallbackLanguage';

		// Unknown, use the default
		if ( $languageCode === '' ) {
			return $this->canonicalFallbackLanguageCode;
		}

		try {
			$contents = $this->languageJsonFileContentsReader->readByLanguageCode( $languageCode );
		} catch ( RuntimeException $e ) {
			$this->fallbackLanguages[$languageCode] = $this->canonicalFallbackLanguageCode;
		}

		// Get customized fallbackLanguage
		if ( isset( $contents[$index] ) ) {
			$this->fallbackLanguages[$languageCode] = $contents[$index];
		}

		// The ultimate defense line, fallback was not set, or is false or empty
		// which means use the canonicalFallbackLanguageCode
		if (
			!isset( $contents[$index] ) ||
			$this->fallbackLanguages[$languageCode] === false ||
			$this->fallbackLanguages[$languageCode] === '' ) {
			$this->fallbackLanguages[$languageCode] = $this->canonicalFallbackLanguageCode;
		}

		return $this->fallbackLanguages[$languageCode];
	}

}
