<?php

namespace SMW\Lang;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageContents {

	/**
	 * @var JsonContentsFileReader
	 */
	private $jsonContentsFileReader;

	/**
	 * @var FallbackFinder
	 */
	private $fallbackFinder;

	/**
	 * @var array
	 */
	private $contents = array();

	/**
	 * @since 2.5
	 *
	 * @param JsonContentsFileReader $jsonContentsFileReader
	 * @param FallbackFinder $fallbackFinder
	 */
	public function __construct( JsonContentsFileReader $jsonContentsFileReader, FallbackFinder $fallbackFinder ) {
		$this->jsonContentsFileReader = $jsonContentsFileReader;
		$this->fallbackFinder = $fallbackFinder;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getCanonicalFallbackLanguageCode() {
		return $this->fallbackFinder->getCanonicalFallbackLanguageCode();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 *
	 * @return boolean
	 */
	public function isLoaded( $languageCode ) {
		return isset( $this->contents[$languageCode] ) || array_key_exists( $languageCode, $this->contents );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 *
	 * @return boolean
	 */
	public function load( $languageCode ) {

		if ( !$this->isLoaded( $languageCode ) && !$this->jsonContentsFileReader->canReadByLanguageCode( $languageCode ) ) {
			$languageCode = $this->fallbackFinder->getFallbackLanguageBy( $languageCode );
		}

		if ( !$this->isLoaded( $languageCode ) ) {
			$this->contents[$languageCode] = $this->jsonContentsFileReader->readByLanguageCode( $languageCode );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param string $index
	 * @param string $languageCode
	 *
	 * @return boolean
	 */
	public function has( $index, $languageCode ) {
		return isset( $this->contents[$languageCode][$index] ) && $this->contents[$languageCode][$index] !== array();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $index
	 * @param string $languageCode
	 *
	 * @return array|string|false
	 */
	public function get( $index, $languageCode ) {

		if ( $this->has( $index, $languageCode ) ) {
			return $this->contents[$languageCode][$index];
		}

		return $this->getFromLanguageById( $languageCode, $index );
	}

	private function getFromLanguageById( $languageCode, $index ) {

		$canonicalFallbackLanguageCode = $this->fallbackFinder->getCanonicalFallbackLanguageCode();

		if ( !isset( $this->contents[$languageCode] ) || $this->contents[$languageCode] === array() ) {
			// In case a language has no matching file
			try {
				$this->contents[$languageCode] = $this->jsonContentsFileReader->readByLanguageCode( $languageCode );
			} catch ( RuntimeException $e ) {
				$this->contents[$languageCode] = array();
				$languageCode = $canonicalFallbackLanguageCode;
			}
		}

		if ( isset( $this->contents[$languageCode][$index] ) && $this->contents[$languageCode][$index] !== array() ) {
			return $this->contents[$languageCode][$index];
		}

		if ( $languageCode !== $canonicalFallbackLanguageCode ) {
			return $this->getFromLanguageById( $this->fallbackFinder->getFallbackLanguageBy( $languageCode ), $index );
		}

		return $this->getCanonicalContentsFrom( $canonicalFallbackLanguageCode, $index );
	}

	private function getCanonicalContentsFrom( $languageCode, $index ) {

		// Last resort before throwing the towel, make sure we really have
		// something when the default FallbackLanguageCode is used
		if ( !isset( $this->contents[$languageCode][$index] ) ) {
			$this->contents[$languageCode] = $this->jsonContentsFileReader->readByLanguageCode( $languageCode, true );
		}

		if ( isset( $this->contents[$languageCode][$index] ) ) {
			return $this->contents[$languageCode][$index];
		}

		throw new RuntimeException( "Unknown or invalid `{$index}` index for `{$languageCode}`"  );
	}

}
