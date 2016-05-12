<?php

namespace SMW\SQLStore\Lookup;

use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CachedListLookup implements ListLookup {

	const VERSION = '0.2';

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	/**
	 * @var Cache
	 */
	private $cache;

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
	 * @var string
	 */
	private $cachePrefix = 'smw:llc:';

	/**
	 * @since 2.2
	 *
	 * @param ListLookup $listLookup
	 * @param Cache $cache
	 * @param stdClass $cacheOptions
	 */
	public function __construct( ListLookup $listLookup, Cache $cache, \stdClass $cacheOptions ) {
		$this->listLookup = $listLookup;
		$this->cache = $cache;
		$this->cacheOptions = $cacheOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $cachePrefix
	 */
	public function setCachePrefix( $cachePrefix ) {
		$this->cachePrefix = $cachePrefix . ':' . $this->cachePrefix;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function fetchList() {

		list( $key, $optionsKey ) = $this->getCacheKey( $this->listLookup->getHash() );

		if ( $this->cacheOptions->useCache && ( ( $result = $this->tryFetchFromCache( $key, $optionsKey ) ) !== null ) ) {
			return $result;
		}

		$list = $this->listLookup->fetchList();

		$this->saveToCache(
			$key,
			$optionsKey,
			$list,
			$this->listLookup->getTimestamp(),
			$this->cacheOptions->ttl
		);

		return $list;
	}

	/**
	 * FIXME NEEDS TO BE REMOVED QUICK
	 * https://github.com/wikimedia/mediawiki-extensions-SemanticForms/blob/master/specials/SF_CreateTemplate.php#L36
	 */
	public function runCollector() {
		return $this->fetchList();
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

		list( $id, $optionsKey ) = $this->getCacheKey(
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

	private function tryFetchFromCache( $key, $optionsKey ) {

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

	private function saveToCache( $key, $optionsKey, $list, $time, $ttl ) {

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

	private function getCacheKey( $id ) {

		$optionsKey = '';

		if ( strpos( $id, '#' ) !== false ) {
			list( $id, $optionsKey ) = explode( '#', $id, 2 );
		}

		return array(
			$this->cachePrefix . md5( $id . self::VERSION ),
			$this->cachePrefix . md5( $id . $optionsKey . self::VERSION )
		);
	}

}
