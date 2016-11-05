<?php

namespace SMW\ExtraneousLanguage;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageContents {

	/**
	 * @var LanguageJsonFileContentsReader
	 */
	private $languageJsonFileContentsReader;

	/**
	 * @var LanguageFallbackFinder
	 */
	private $languageFallbackFinder;

	/**
	 * @var array
	 */
	private $contents = array();

	/**
	 * @since 2.5
	 *
	 * @param LanguageJsonFileContentsReader $languageJsonFileContentsReader
	 * @param LanguageFallbackFinder $languageFallbackFinder
	 */
	public function __construct( LanguageJsonFileContentsReader $languageJsonFileContentsReader, LanguageFallbackFinder $languageFallbackFinder ) {
		$this->languageJsonFileContentsReader = $languageJsonFileContentsReader;
		$this->languageFallbackFinder = $languageFallbackFinder;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getCanonicalFallbackLanguageCode() {
		return $this->languageFallbackFinder->getCanonicalFallbackLanguageCode();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 *
	 * @return boolean
	 */
	public function has( $languageCode ) {
		return isset( $this->contents[$languageCode] ) || array_key_exists( $languageCode, $this->contents );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 *
	 * @return boolean
	 */
	public function prepareWithLanguage( $languageCode ) {

		if ( !$this->has( $languageCode ) && !$this->languageJsonFileContentsReader->canReadByLanguageCode( $languageCode ) ) {
			$languageCode = $this->languageFallbackFinder->getFallbackLanguageBy( $languageCode );
		}

		if ( !$this->has( $languageCode ) ) {
			$this->contents[$languageCode] = $this->languageJsonFileContentsReader->readByLanguageCode( $languageCode );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 * @param string $index
	 *
	 * @return boolean
	 */
	public function hasLanguageWithIndex( $languageCode, $index ) {
		return isset( $this->contents[$languageCode][$index] ) && $this->contents[$languageCode][$index] !== array();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 * @param string $index
	 *
	 * @return array|string|false
	 */
	public function getContentsByLanguageWithIndex( $languageCode, $index ) {

		if ( $this->hasLanguageWithIndex( $languageCode, $index ) ) {
			return $this->contents[$languageCode][$index];
		}

		return $this->getFromLanguageWithIndex( $languageCode, $index );
	}

	private function getFromLanguageWithIndex( $languageCode, $index ) {

		$canonicalFallbackLanguageCode = $this->languageFallbackFinder->getCanonicalFallbackLanguageCode();

		if ( !isset( $this->contents[$languageCode] ) || $this->contents[$languageCode] === array() ) {
			// In case a language has no matching file
			try {
				$this->contents[$languageCode] = $this->languageJsonFileContentsReader->readByLanguageCode( $languageCode );
			} catch ( RuntimeException $e ) {
				$this->contents[$languageCode] = array();
				$languageCode = $canonicalFallbackLanguageCode;
			}
		}

		if ( isset( $this->contents[$languageCode][$index] ) && $this->contents[$languageCode][$index] !== array() ) {
			return $this->contents[$languageCode][$index];
		}

		if ( $languageCode !== $canonicalFallbackLanguageCode ) {
			return $this->getFromLanguageWithIndex( $this->languageFallbackFinder->getFallbackLanguageBy( $languageCode ), $index );
		}

		return $this->getCanonicalContentsFrom( $canonicalFallbackLanguageCode, $index );
	}

	private function getCanonicalContentsFrom( $languageCode, $index ) {

		// Last resort before throwing the towel, make sure we really have
		// something when the default FallbackLanguageCode is used
		if ( !isset( $this->contents[$languageCode][$index] ) ) {
			$this->contents[$languageCode] = $this->languageJsonFileContentsReader->readByLanguageCode( $languageCode, true );
		}

		if ( isset( $this->contents[$languageCode][$index] ) ) {
			return $this->contents[$languageCode][$index];
		}

		throw new RuntimeException( "Unknown or invalid `{$index}` index for `{$languageCode}`"  );
	}

}
