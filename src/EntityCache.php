<?php

namespace SMW;

use Onoi\Cache\Cache;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class EntityCache {

	const CACHE_NAMESPACE = 'smw:entity';

	const VERSION = 1;

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
		return smwfCacheKey( self::CACHE_NAMESPACE, $params + [ 'version' => self::VERSION ] );
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
	 * @param mixed $value
	 */
	public function save( $key, $value = null, $ttl = 0 ) {
		 $this->cache->save( $key, $value, $ttl );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
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
	 */
	public function delete( $key ) {
		 $this->cache->delete( $key );
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

		$k = $this->makeCacheKey( $subject->getHash() );
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

		$k = $this->makeCacheKey( $subject->getHash() );
		$res = $this->cache->fetch( $k );

		if ( isset( $res['__assoc'] ) ) {
			foreach ( $res['__assoc'] as $key => $bool ) {
				$this->cache->delete( $key );
			}
		}

		$this->cache->delete( $k );
	}

}
