<?php

namespace SMW;

use Onoi\Cache\Cache;
use Title;

/**
 * Class provides a simple interface the link independent cache entries as
 * associates (to a wikipage) hereby allowing them to be invalidated at once.
 *
 * `...Sub` methods provide a convenient support layer to extend or remove values
 * from a cache entry.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class EntityCache {

	const CACHE_NAMESPACE = 'smw:entity';
	const VERSION = 1;

	const TTL_SECOND = 1;
	const TTL_MINUTE = 60;
	const TTL_HOUR = 3600;
	const TTL_DAY = 86400; // 24 * 3600
	const TTL_WEEK = 604800; // 7 * 24 * 3600
	const TTL_MONTH = 2592000; // 30 * 24 * 3600
	const TTL_YEAR = 31536000; // 365 * 24 * 3600

	/**
	 * @var Cache
	 */
	private $cache = null;

	/**
	 * @since 3.1
	 *
	 * @param Cache $cache
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @since 3.1
	 *
	 * @param string|array $key
	 *
	 * @return string
	 */
	public static function makeCacheKey( ...$params ) {

		if ( $params[0] instanceof Title ) {
			$params[0] = DIWikiPage::newFromTitle( $params[0] );
		}

		if ( $params[0] instanceof DIWikiPage ) {
			$params[0] = $params[0]->getHash();
		}

		return smwfCacheKey( self::CACHE_NAMESPACE, $params + [ 'version' => self::VERSION ] );
	}

	/**
	 * @since 3.1
	 *
	 * @param string|array $key
	 *
	 * @return string
	 */
	public function makeKey( ...$params ) {
		return self::makeCacheKey( ...$params );
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getStats() {
		return $this->cache->getStats();
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function contains( $key ) {
		return $this->cache->contains( $key );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function fetch( $key ) {
		return $this->cache->fetch( $key );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function save( $key, $value = null, $ttl = 0 ) {
		 $this->cache->save( $key, $value, $ttl );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 */
	public function delete( $key ) {
		$this->cache->delete( $key );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function fetchSub( $key, $sub ) {

		$res = $this->cache->fetch( $key );
		$sub = md5( $sub );

		if ( !is_array( $res ) ) {
			return false;
		}

		return isset( $res[$sub] ) ? $res[$sub] : false;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param string $sub
	 * @param mixed $value
	 * @param integer $ttl
	 */
	public function saveSub( $key, $sub, $value = null, $ttl = 0 ) {

		$res = $this->cache->fetch( $key );
		$sub = md5( $sub );

		if ( !is_array( $res ) ) {
			$res = [];
		}

		$res[$sub] = $value;

		$this->cache->save( $key, $res, $ttl );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param string $sub
	 * @param mixed $value
	 * @param integer $ttl
	 */
	public function overrideSub( $key, $sub, $value = null, $ttl = 0 ) {

		$res = [
			md5( $sub ) => $value
		];

		$this->cache->save( $key, $res, $ttl );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param string $sub
	 * @param integer $ttl
	 */
	public function deleteSub( $key, $sub, $ttl = 0 ) {

		$res = $this->cache->fetch( $key );
		$sub = md5( $sub );

		if ( !is_array( $res ) ) {
			$res = [];
		}

		unset( $res[$sub] );

		$this->cache->save( $key, $res, $ttl );
	}

	/**
	 * Bind a cache key to a subject so that when a page gets flushed or
	 * modified, any associate keys can be invalidated at once.
	 *
	 * @since 3.1
	 *
	 * @param DIWikiPage|Title $subject
	 */
	public function associate( $subject = null, $key ) {

		if ( $subject === null ) {
			return;
		}

		if ( $subject instanceof Title ) {
			$subject = DIWikiPage::newFromTitle( $subject );
		}

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		// Associate only with the main subject (given that a Title can be injected)
		$subject = $subject->asBase();

		$k = $this->makeCacheKey( $subject );
		$res = $this->cache->fetch( $k );

		// Initialize the record that binds the "page" entity to all associated
		// subkeys
		if ( !isset( $res['__subject'] ) ) {
			$res['__subject'] = $subject->getHash();
		}

		if ( !isset( $res['__assoc'] ) ) {
			$res['__assoc'] = [];
		}

		$res['__assoc'][$key] = true;

		// Store without expiry
		$this->cache->save( $k, $res );
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage|Title $subject
	 */
	public function invalidate( $subject = null ) {

		if ( $subject === null ) {
			return;
		}

		if ( $subject instanceof Title ) {
			$subject = DIWikiPage::newFromTitle( $subject );
		}

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		$subject = $subject->asBase();

		$k = $this->makeCacheKey( $subject );
		$res = $this->cache->fetch( $k );

		if ( isset( $res['__assoc'] ) ) {
			foreach ( $res['__assoc'] as $key => $bool ) {
				$this->cache->delete( $key );
			}
		}

		$this->cache->delete( $k );
	}

}
