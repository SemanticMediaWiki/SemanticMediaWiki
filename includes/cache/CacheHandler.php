<?php

namespace SMW;

use ObjectCache;
use BagOStuff;

/**
 * This class is handling access to cacheable entities
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * This class is handling access to cacheable entities
 *
 * @ingroup Handler
 */
final class CacheHandler {

	/** @var BagOStuff */
	protected $cache = null;

	/** @var string */
	protected $key = false;

	/** @var string */
	protected $prefix;

	/** @var boolean */
	protected $cacheEnabled = false;

	/** @var CacheHandler[] */
	private static $instance = array();

	/**
	 * @since 1.9
	 *
	 * @param BagOStuff|null $cache
	 */
	public function __construct( BagOStuff $cache = null ) {
		$this->cache = $cache;
	}

	/**
	 * Factory method that creates a CacheHandler instance and instantiate a
	 * BagOStuff object from available settings ($smwgCacheType) while
	 * invoking additional parameters ($wgCachePrefix etc.)
	 *
	 * @par Example:
	 * @code
	 *  $cache = new CacheHandler::newFromId()->key( 'Foo', 'Bar' )
	 *
	 *  $cache->set( 'CacheableObject' )
	 *  $cache->get() returns 'CacheableObject'
	 *  $cache->delete() deletes 'CacheableObject'
	 * @endcode
	 *
	 * @note If a BagOStuff instance is not available setCacheEnabled() is
	 * disabled by default which prevents to run into unintended behaviour
	 * while trying to access BagOStuff methods.
	 *
	 * @note This method is exposed to $wgObjectCaches, $wgCachePrefix globals
	 * which can't and shouldn't be accessed otherwise. It is the task of this
	 * method alone to invoke globals and avoid leakage into the object
	 * life cycle.
	 *
	 * @note This method serves invoked instances from a static variable due to
	 * the fact that the actual working object is being accessed through
	 * getCache() and therefore not in direct conflict with its testability.
	 *
	 * @since 1.9
	 *
	 * @param string $id Ids available in wgObjectCaches
	 *
	 * @return CacheHandler
	 */
	public static function newFromId( $id = false ) {

		$cacheType = $id ? $id : Settings::newFromGlobals()->get( 'smwgCacheType' );

		if ( !isset( self::$instance[$cacheType] ) ) {

			if ( $cacheType && array_key_exists( $cacheType, $GLOBALS['wgObjectCaches'] ) ) {
				$cache = new self( ObjectCache::getInstance( $cacheType ) );
			} else {
				$cache = new self;
			}

			$cache->setCachePrefix( $GLOBALS['wgCachePrefix'] === false ? wfWikiID() : $GLOBALS['wgCachePrefix'] );
			$cache->setCacheEnabled( true );

			self::$instance[$cacheType] = $cache;
		}

		return self::$instance[$cacheType];
	}

	/**
	 * Returns key
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Sets key
	 *
	 * @since 1.9
	 *
	 * @param IdGenerator $id
	 *
	 * @return CacheHandler
	 */
	public function setKey( IdGenerator $id ) {
		$this->key = $id->generateId();
		return $this;
	}

	/**
	 * Returns invoked cache instance
	 *
	 * @since 1.9
	 *
	 * @return BagOStuff|null
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * Generates and invokes a concatenated string containing <prefix>:smw:<key>
	 *
	 * @par Example:
	 * @code
	 *  $cache = new CacheHandler::newFromId()
	 *
	 *  $cache->key( 'Foo', 'Bar' ) generates <prefix>:smw:Foo:Bar
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param varargs
	 *
	 * @return CacheHandler
	 */
	public function key( /* ... */ ) {
		$this->key = $this->prefix . ':' . 'smw' . ':' . str_replace( ' ', '_', implode( ':', func_get_args() ) );
		return $this;
	}

	/**
	 * Stores an object in cache for the invoked key
	 *
	 * @since 1.9
	 *
	 * @param mixed $value
	 * @param int $exptime
	 *
	 * @return boolean
	 */
	public function set( $value, $exptime = 0 ) {
		return $this->isEnabled() ? $this->getCache()->set( $this->getKey(), $value, $exptime ) : false;
	}

	/**
	 * Returns object from cache for the invoked key
	 *
	 * @since 1.9
	 *
	 * @return mixed|false
	 */
	public function get() {
		return $this->isEnabled() ? $this->getCache()->get( $this->getKey() ) : false;
	}

	/**
	 * Deletes object from cache for the invoked key
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function delete() {
		return $this->isEnabled() ? $this->getCache()->delete( $this->getKey() ) : false;
	}

	/**
	 * Sets availability for the current instance
	 *
	 * @note It will assert the availability of the BagOStuff object by default
	 * and return false independently from the parameter invoked (this
	 * safeguards against overriding the availability status of a non-BagOStuff
	 * instance)
	 *
	 * @since 1.9
	 *
	 * @param boolean $cacheEnabled
	 *
	 * @return CacheHandler
	 */
	public function setCacheEnabled( $cacheEnabled ) {
		$this->cacheEnabled = $this->getCache() instanceof BagOStuff ? (bool)$cacheEnabled : false;
		return $this;
	}

	/**
	 * Sets cache prefix
	 *
	 * @see $wgCachePrefix
	 *
	 * @since 1.9
	 *
	 * @param string $prefix
	 *
	 * @return CacheHandler
	 */
	public function setCachePrefix( $prefix ) {
		$this->prefix = $prefix;
		return $this;
	}

	/**
	 * Returns current status of the cache instance
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->cacheEnabled && $this->key;
	}

	/**
	 * Reset instance
	 *
	 * @since 1.9
	 */
	public static function reset() {
		self::$instance = array();
	}
}
