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
 * @since 1.9
 *
 * @file
 * @ingroup Store
 *
 * @author mwjames
 */

/**
 * Factory method that handles store instantiation
 *
 * @todo instead of having a single store declaration such as'smwgDefaultStore' => 'MyStore'
 * allow for a more fain-grained definition such as
 *
 * @code
 * 'smwgStores' => array(
 *   'SqlStore' => 'MySqlStore'
 *   'SparqlStore' => 'MySparqlStore'
 *   'HashStore' => 'MyHashStore' // For unit testing to omit direct database access
 *   ...
 * )
 * @endcode
 *
 * @ingroup Store
 */
class StoreFactory {

	/**
	 * Returns a new store instance
	 *
	 * @since 1.9
	 *
	 * @param string $store
	 *
	 * @return Store
	 * @throws StoreInstanceException
	 */
	public static function newInstance( $store ) {

		$instance = new $store;

		if ( !( $instance instanceof Store ) ) {
			throw new StoreInstanceException( "{$store} can not be used as a store instance" );
		}

		return $instance;
	}

	/**
	 * Returns an instance of the default store, or an alternative store
	 *
	 * @since 1.9
	 *
	 * @param boolean|string $store
	 *
	 * @return Store
	 */
	public static function getStore( $store = false ) {
		static $instance = array();

		$store = $store === false ? Settings::newFromGlobals()->get( 'smwgDefaultStore' ) : $store;

		if ( !isset( $instance[$store] ) ) {
			$instance[$store] = self::newInstance( $store );
		}

		return $instance[$store];
	}
}
