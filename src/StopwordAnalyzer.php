<?php

namespace Onoi\Tesa;

use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class StopwordAnalyzer {

	/**
	 * Any change to the content of its data files should be reflected in a
	 * version change (the version number does not necessarily correlate with
	 * the library version)
	 */
	const VERSION = '0.1.3';

	/**
	 * Prefix
	 */
	const CACHE = 'onoi:tesa:stopword:';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var integer
	 */
	private $ttl = 3600;

	/**
	 * @var array|null
	 */
	private static $internalLookupCache = null;

	/**
	 * @var array
	 */
	private $languageList = array();

	/**
	 * @since 0.1
	 *
	 * @param Cache|null $cache
	 * @param integer $ttl
	 */
	public function __construct( Cache $cache = null, $ttl = 3600 ) {
		$this->cache = $cache;
		$this->ttl = $ttl;

		if ( $this->cache === null ) {
			$this->cache = new NullCache();
		}

		self::$internalLookupCache = array();
		$this->languageList = array();
	}

	/**
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getLanguageList() {
		return $this->languageList;
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $flag
	 */
	public function loadListByDefaultLanguages() {
		$this->languageList = array( 'en', 'de', 'es', 'fr' );
		$this->loadListByLanguage( $this->languageList );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $location
	 * @param string|array $languageCode
	 */
	public function loadListFromCustomLocation( $location, $languageCode ) {

		$this->languageList = (array)$languageCode;

		$this->loadListFromCache(
			str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $location ),
			$this->languageList
		);
	}

	/**
	 * @since 0.1
	 *
	 * @param string|array $languageCode
	 */
	public function loadListByLanguage( $languageCode ) {

		$this->languageList = (array)$languageCode;

		$this->loadListFromCache(
			str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, __DIR__ . '/../data/stopwords/' ),
			$this->languageList
		);
	}

	/**
	 * The expected form is array( $languageCode => array( 'foo', 'bar' ) )
	 *
	 * @since 0.1
	 *
	 * @param array $customStopwordList
	 */
	public function setCustomStopwordList( array $customStopwordList ) {

		self::$internalLookupCache = array();
		$this->languageList = array();

		foreach ( $customStopwordList as $languageCode => $contents ) {
			self::$internalLookupCache += array_fill_keys( $contents, true );
			$this->languageList[$languageCode] = true;
		}

		$this->languageList = array_keys( $this->languageList );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $word
	 *
	 * @return boolean
	 */
	public function isStopWord( $word ) {
		return isset( self::$internalLookupCache[$word] );
	}

	private function loadListFromCache( $location, $languages ) {

		self::$internalLookupCache = array();
		$id = self::CACHE . md5( json_encode( $languages ) . $location . $this->ttl . self::VERSION );

		if ( $this->cache->contains( $id ) ) {
			return self::$internalLookupCache = $this->cache->fetch( $id );
		}

		foreach ( $languages as $languageCode ) {

			// We silently ignore any error on purpose
			$contents = json_decode(
				@file_get_contents( $location . $languageCode . '.json' ),
				true
			);

			if ( $contents === null || json_last_error() !== JSON_ERROR_NONE || !isset( $contents[$languageCode] ) ) {
				continue;
			}

			self::$internalLookupCache += array_fill_keys( $contents[$languageCode], true );
		}

		$this->cache->save( $id, self::$internalLookupCache );
	}

}
