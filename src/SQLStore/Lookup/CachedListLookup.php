<?php

namespace SMW\SQLStore\Lookup;

use Onoi\Cache\Cache;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CachedListLookup implements ListLookup {

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
	private $isCached = false;

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

		list( $key, $optionsKey ) = $this->getCacheKey( $this->listLookup->getLookupIdentifier() );

		if ( $this->cacheOptions->useCache && $this->cache->contains( $key ) && ( ( $result = $this->tryFetchFromCache( $key, $optionsKey ) ) !== null ) ) {
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
	public function isCached() {
		return $this->isCached;
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
	public function getLookupIdentifier() {
		return $this->listLookup->getLookupIdentifier();
	}

	/**
	 * @since 2.3
	 */
	public function deleteCache() {

		list( $id, $optionsKey ) = $this->getCacheKey(
			$this->listLookup->getLookupIdentifier()
		);

		$this->cache->delete( $id );
	}

	private function tryFetchFromCache( $key, $optionsKey ) {

		$data = unserialize( $this->cache->fetch( $key ) );

		if ( !isset( $data[$optionsKey] ) ) {
			return null;
		}

		$this->isCached = true;
		$this->timestamp = $data[$optionsKey]['time'];

		return $data[$optionsKey]['list'];
	}

	private function saveToCache( $key, $optionsKey, $list, $time, $ttl ) {

		$this->timestamp = $time;
		$this->isCached = false;

		$data = unserialize( $this->cache->fetch( $key ) );

		$data[$optionsKey] = array(
			'time' => $this->timestamp,
			'list' => $list
		);

		$this->cache->save( $key, serialize( $data ), $ttl );
	}

	private function getCacheKey( $id ) {

		$optionsKey = '';

		if ( strpos( $id, '#' ) !== false ) {
			list( $id, $optionsKey ) = explode( '#', $id, 2 );
		}

		return array(
			$this->cachePrefix . md5( $id ),
			md5( $optionsKey )
		);
	}

}
