<?php

namespace SMW;

use InvalidArgumentException;
use MWTimestamp;

/**
 * Handling of cached results
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
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * Handling of cached results
 *
 * @ingroup SMW
 */
class ResultCacheMapper implements Cacheable {

	/** @var ArrayAccessor */
	protected $cacheAccessor;

	/**
	 * @since 1.9
	 *
	 * @param ArrayAccessor $cacheAccessor
	 */
	public function __construct( ArrayAccessor $cacheAccessor ) {
		$this->cacheAccessor = $cacheAccessor;
	}

	/**
	 * Service function that fetches and returns results from cache
	 *
	 * @note Id generation has been delegated to CacheIdGenerator
	 *
	 * @since 1.9
	 *
	 * @return array|false
	 */
	public function fetchFromCache() {

		$result = $this->getCache()
			->setCacheEnabled( $this->cacheAccessor->get( 'enabled' ) )
			->setKey( new CacheIdGenerator( $this->cacheAccessor->get( 'id' ), $this->cacheAccessor->get( 'prefix' ) ) )
			->get();

		return $result ? $this->mapping( $result ) : $result;
	}

	/**
	 * Service function that stores results as a cache object
	 *
	 * @note The cache object stores the time and its results as serialized
	 * array in order to allow any arbitrary content to be cacheable
	 *
	 * @note Results are serialized as they can contain an array of objects
	 * where when retrieved from cache those objects are going to be
	 * unserialized to restore the original object
	 *
	 * @since 1.9
	 *
	 * @param array $results
	 */
	public function recache( array $results ) {

		$this->getCache()
			->setCacheEnabled( $this->cacheAccessor->get( 'enabled' ) && $results !== array() )
			->set( array( 'time' => $this->getTimestamp(), 'result' => serialize( $results ) ), $this->cacheAccessor->get( 'expiry' )
		);
	}

	/**
	 * Returns a CacheHandler instance
	 *
	 * @since 1.9
	 *
	 * @return CacheHandler
	 */
	public function getCacheDate() {
		return $this->cacheAccessor->has( 'cacheDate' ) ? $this->cacheAccessor->get( 'cacheDate' ) : null;
	}

	/**
	 * Returns a CacheHandler instance
	 *
	 * @since 1.9
	 *
	 * @return CacheHandler
	 */
	public function getCache() {
		return CacheHandler::newFromId( $this->cacheAccessor->get( 'type' ) );
	}

	/**
	 * Service function that remaps an array of cached content and returns
	 * unserialized objects and the timestamp of the cached content
	 *
	 * @since 1.9
	 *
	 * @param array $resultCache
	 *
	 * @return array
	 */
	protected function mapping( array $resultCache ) {
		$this->cacheAccessor->set( 'cacheDate', isset( $resultCache['time'] ) ? $resultCache['time'] : null );
		return isset( $resultCache['result'] ) ? unserialize( $resultCache['result'] ) : array();
	}

	/**
	 * Returns a timestamp
	 *
	 * @todo Apparently MW 1.19 does not have a MWTimestamp class, please
	 * remove this clutter as soon as MW 1.19 is not supported any longer
	 *
	 * @since 1.9
	 *
	 * @return integer
	 */
	protected function getTimestamp() {
		if ( class_exists( 'MWTimestamp' ) ) {
			$timestamp = new MWTimestamp();
			return $timestamp->getTimestamp( TS_UNIX );
		} else {
			// @codeCoverageIgnoreStart
			return wfTimestamp( TS_UNIX );
			// @codeCoverageIgnoreEnd
		}
	}
}
