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
	 * Any change to the content of this file should be reflected in a version
	 * change (the version is not necessarily the same as the library).
	 */
	const VERSION = '0.1.2';

	const CACHE = 'onoi:tesa:stopword:';

	/**
	 * Supported options
	 */
	const NONE = 0x2;
	const DEFAULT_STOPWORDLIST = 0x4;

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
	}

	/**
	 * @since 0.1
	 *
	 * @param integer $flag
	 */
	public function loadListBy( $flag ) {

		self::$internalLookupCache = array();

		if ( $flag === ( $flag | self::DEFAULT_STOPWORDLIST ) ) {
			$this->loadListFromCache(
				str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, __DIR__ . '/../data/stopwords/' ),
				array( 'en', 'de', 'es', 'fr' )
			);
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param string $languageCode
	 */
	public function loadListByLanguage( $languageCode ) {

		self::$internalLookupCache = array();

		$this->loadListFromCache(
			str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, __DIR__ . '/../data/stopwords/' ),
			array( $languageCode )
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

		foreach ( $customStopwordList as $languageCode => $contents ) {
			self::$internalLookupCache += array_fill_keys( $contents, true );
		}
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

		$id = self::CACHE . md5( json_encode( $languages ) . $this->ttl . self::VERSION );

		if ( $this->cache->contains( $id ) ) {
			return self::$internalLookupCache = $this->cache->fetch( $id );
		}

		self::$internalLookupCache = array();

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
