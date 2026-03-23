<?php

namespace SMW\Localizer\LocalLanguage;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FallbackFinder {

	private string $canonicalFallbackLanguageCode = 'en';

	private array $fallbackLanguages = [];

	/**
	 * @since 2.5
	 */
	public function __construct( private readonly JsonContentsFileReader $jsonContentsFileReader ) {
	}

	/**
	 * @since 2.5
	 */
	public function emptyByLanguageCode( $languageCode ): void {
		unset( $this->fallbackLanguages[strtolower( trim( $languageCode ) )] );
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getCanonicalFallbackLanguageCode(): string {
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

		$index = 'fallback_language';

		// Unknown, use the default
		if ( $languageCode === '' ) {
			return $this->canonicalFallbackLanguageCode;
		}

		try {
			$contents = $this->jsonContentsFileReader->readByLanguageCode( $languageCode );
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
