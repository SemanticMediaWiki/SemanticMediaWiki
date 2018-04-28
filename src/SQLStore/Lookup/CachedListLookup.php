<?php

namespace SMW\SQLStore\Lookup;

use Onoi\Cache\Cache;
use SMW\Options;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CachedListLookup implements ListLookup {

	const VERSION = '0.2';

	/**
	 * Identifies the cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:store:lookup';

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var stdClass
	 */
	private $cacheOptions;

	/**
	 * @var boolean
	 */
	private $isFromCache = false;

	/**
	 * @var integer
	 */
	private $timestamp;

	/**
	 * @since 2.2
	 *
	 * @param ListLookup $listLookup
	 * @param Cache $cache
	 */
	public function __construct( ListLookup $listLookup, Cache $cache ) {
		$this->listLookup = $listLookup;
		$this->cache = $cache;
		$this->options = new Options();
	}

	/**
	 * @since 3.0
	 */
	public function invalidateCache() {

		list( $id, $optionsKey ) = $this->makeCacheKey(
			$this->listLookup->getHash()
		);

		$data = unserialize( $this->cache->fetch( $id ) );

		if ( $data && $data !== array() ) {
			foreach ( $data as $key => $value ) {
				$this->cache->delete( $key );
			}
		}

		$this->cache->delete( $id );
	}

	/**
	 * @since 3.0
	 */
	public function setOption( $key, $value ) {
		$this->options->set( $key, $value );
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function lookup() {

		list( $key, $optionsKey ) = $this->makeCacheKey( $this->listLookup->getHash() );

		if ( $this->options->safeGet( self::OPT_USE_CACHE, false ) && ( ( $result = $this->fetchFromCache( $key, $optionsKey ) ) !== null ) ) {
			return $result;
		}

		$list = $this->listLookup->lookup();

		$this->save(
			$key,
			$optionsKey,
			$list,
			$this->listLookup->getTimestamp(),
			$this->options->safeGet( self::OPT_CACHE_TTL, 600 )
		);

		return $list;
	}

	/**
	 * FIXME NEEDS TO BE REMOVED QUICK
	 * https://github.com/wikimedia/mediawiki-extensions-SemanticForms/blob/master/specials/SF_CreateTemplate.php#L36
	 */
	public function runCollector() {
		return $this->lookup();
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function isFromCache() {
		return $this->isFromCache;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->listLookup->getHash();
	}

	/**
	 * @since 2.3
	 */
	public function deleteCache() {
		$this->invalidateCache();
	}

	private function fetchFromCache( $key, $optionsKey ) {

		if ( !$this->cache->contains( $key ) ) {
			return null;
		}

		$data = unserialize( $this->cache->fetch( $optionsKey ) );

		if ( $data === array() ) {
			return null;
		}

		$this->isFromCache = true;
		$this->timestamp = $data['time'];

		return $data['list'];
	}

	private function save( $key, $optionsKey, $list, $time, $ttl ) {

		$this->timestamp = $time;
		$this->isFromCache = false;

		// Collect the options keys
		$data = unserialize( $this->cache->fetch( $key ) );
		$data[$optionsKey] = true;
		$this->cache->save( $key, serialize( $data ), $ttl );

		$data = array(
			'time' => $this->timestamp,
			'list' => $list
		);

		$this->cache->save( $optionsKey, serialize( $data ), $ttl );
	}

	private function makeCacheKey( $id ) {

		$optionsKey = '';

		if ( strpos( $id, '#' ) !== false ) {
			list( $id, $optionsKey ) = explode( '#', $id, 2 );
		}

		return [
			smwfCacheKey( self::CACHE_NAMESPACE, $id . self::VERSION ),
			smwfCacheKey( self::CACHE_NAMESPACE, $id . $optionsKey . self::VERSION )
		];
	}

}
