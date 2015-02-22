<?php

namespace SMW\SQLStore;

use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ListLookupCache implements SimpleListLookup {

	/**
	 * @var SimpleListLookup
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
	private $cachePrefix = '';

	/**
	 * @since 2.2
	 *
	 * @param SimpleListLookup $listLookup
	 * @param Cache $cache
	 * @param stdClass $cacheOptions
	 */
	public function __construct( SimpleListLookup $listLookup, Cache $cache, \stdClass $cacheOptions ) {
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
		$this->cachePrefix = $cachePrefix;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function fetchResultList() {

		$key = $this->getCacheKey( $this->listLookup->getLookupIdentifier() );

		if ( $this->cache->contains( $key ) && $this->cacheOptions->useCache ) {
			return $this->retrieveFromCache( $key );
		}

		$list = $this->listLookup->fetchResultList();

		$this->saveToCache(
			$key,
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
		return $this->fetchResultList();
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

	private function retrieveFromCache( $key ) {

		$data = $this->cache->fetch( $key );

		$this->isCached = true;
		$this->timestamp = $data['time'];

		return unserialize( $data['list'] );
	}

	private function saveToCache( $key, $list, $time, $ttl ) {

		$this->timestamp = $time;
		$this->isCached = false;

		$data = array(
			'time' => $this->timestamp,
			'list' => serialize( $list )
		);

		$this->cache->save( $key, $data, $ttl );
	}

	private function getCacheKey( $id ) {

		if ( $this->cachePrefix === '' ) {
			$this->cachePrefix = $GLOBALS['wgCachePrefix'] === false ? wfWikiID() : $GLOBALS['wgCachePrefix'];
		}

		return $this->cachePrefix . ':' . 'smw:' . 'lookup-cache:' . md5( $id );
	}

}
