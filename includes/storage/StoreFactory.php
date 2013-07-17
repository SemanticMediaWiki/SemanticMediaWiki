<?php

namespace SMW;

/**
 * Factory method that handles store instantiation
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
 * Factory method that handles store instantiation
 *
 * @ingroup Store
 */
class StoreFactory {

	/** @var Store[] */
	private static $instance = array();

	/**
	 * Returns a new store instance
	 *
	 * @since 1.9
	 *
	 * @param string $store
	 *
	 * @return Store
	 * @throws InvalidStoreException
	 */
	public static function newInstance( $store ) {

		$instance = new $store;

		if ( !( $instance instanceof Store ) ) {
			throw new InvalidStoreException( "{$store} can not be used as a store instance" );
		}

		return $instance;
	}

	/**
	 * Returns an instance of the default store, or an alternative store
	 *
	 * @since 1.9
	 *
	 * @param string|null $store
	 *
	 * @return Store
	 */
	public static function getStore( $store = null ) {

		$store = $store === null ? Settings::newFromGlobals()->get( 'smwgDefaultStore' ) : $store;

		if ( !isset( self::$instance[$store] ) ) {
			self::$instance[$store] = self::newInstance( $store );
		}

		return self::$instance[$store];
	}

	/**
	 * Reset instance
	 *
	 * @since 1.9
	 */
	public static function clear() {
		self::$instance = array();
	}
}
