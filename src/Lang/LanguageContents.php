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
	private $contents = [];

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
	 * @param string $id
	 * @param string $languageCode
	 *
	 * @return array|string|false
	 */
	public function get( $id, $languageCode ) {
		return $this->matchLanguage( $languageCode, $id );
	}

	private function matchLanguage( $languageCode, $id ) {

		$canonicalFallbackLanguageCode = $this->fallbackFinder->getCanonicalFallbackLanguageCode();

		if ( !isset( $this->contents[$languageCode] ) || $this->contents[$languageCode] === [] ) {
			// In case a language has no matching file
			try {
				$this->contents[$languageCode] = $this->jsonContentsFileReader->readByLanguageCode( $languageCode );
			} catch ( RuntimeException $e ) {
				$this->contents[$languageCode] = [];
				$languageCode = $canonicalFallbackLanguageCode;
			}
		}

		$depth = 1;

		// There is certainly a better (meaning generic) way to do this yet with
		// only a limited depth, doing a recursive traversal will not yield an
		// advantage
		if ( strpos( $id, '.' ) !== false ) {
			$keys = explode( '.', $id );
			$depth = count( $keys );
		}

		if ( $depth == 1 && isset( $this->contents[$languageCode][$id] ) && $this->contents[$languageCode][$id] !== [] ) {
			return $this->contents[$languageCode][$id];
		}

		if ( $depth == 2 && isset( $this->contents[$languageCode][$keys[0]][$keys[1]] ) && $this->contents[$languageCode][$keys[0]][$keys[1]] !== [] ) {
			return $this->contents[$languageCode][$keys[0]][$keys[1]];
		}

		if ( $depth == 3 && isset( $this->contents[$languageCode][$keys[0]][$keys[1]][$keys[2]] ) && $this->contents[$languageCode][$keys[0]][$keys[1]][$keys[2]] !== [] ) {
			return $this->contents[$languageCode][$keys[0]][$keys[1]][$keys[2]];
		}

		if ( $languageCode !== $canonicalFallbackLanguageCode ) {
			return $this->matchLanguage( $this->fallbackFinder->getFallbackLanguageBy( $languageCode ), $id );
		}

		return $this->matchCanonicalLanguage( $canonicalFallbackLanguageCode, $id );
	}

	private function matchCanonicalLanguage( $languageCode, $id ) {

		$depth = 1;

		if ( strpos( $id, '.' ) !== false ) {
			$keys = explode( '.', $id );
			$depth = count( $keys );
		}

		// Last resort before throwing the towel, make sure we really have
		// something when the default FallbackLanguageCode is used
		if ( $depth == 1 && !isset( $this->contents[$languageCode][$id] ) ) {
			$this->contents[$languageCode] = $this->jsonContentsFileReader->readByLanguageCode( $languageCode, true );
		}

		if ( $depth == 1 && isset( $this->contents[$languageCode][$id] ) ) {
			return $this->contents[$languageCode][$id];
		}

		if ( $depth == 2 && !isset( $this->contents[$languageCode][$keys[0]][$keys[1]] ) ) {
			$this->contents[$languageCode] = $this->jsonContentsFileReader->readByLanguageCode( $languageCode, true );
		}

		if ( $depth == 2 && isset( $this->contents[$languageCode][$keys[0]][$keys[1]] ) ) {
			return $this->contents[$languageCode][$keys[0]][$keys[1]];
		}

		if ( $depth == 3 && !isset( $this->contents[$languageCode][$keys[0]][$keys[1]][$keys[2]] ) ) {
			$this->contents[$languageCode] = $this->jsonContentsFileReader->readByLanguageCode( $languageCode, true );
		}

		if ( $depth == 3 && isset( $this->contents[$languageCode][$keys[0]][$keys[1]][$keys[2]] ) ) {
			return $this->contents[$languageCode][$keys[0]][$keys[1]][$keys[2]];
		}

		throw new RuntimeException( "Unknown or invalid `{$id}` id for `{$languageCode}`"  );
	}

}
