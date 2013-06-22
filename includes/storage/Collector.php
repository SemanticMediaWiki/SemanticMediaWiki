<?php

namespace SMW\Store;

use SMW\CacheHandler;
use SMW\DIProperty;
use SMW\Settings;

use MWTimestamp;

/**
 * Interface for items of groups of individuals to be sampled into a
 * collection of values
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
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
interface Collectible {}

/**
 * Collectors base class
 *
 * @ingroup SMW
 */
abstract class Collector implements Collectible {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/** @var boolean */
	protected $isCached = false;

	/**
	 * Collects and returns information in an associative array
	 *
	 * @since 1.9
	 */
	public abstract function getResults();

	/**
	 * Returns if the results are cached
	 *
	 * @since 1.9
	 */
	public abstract function isCached();

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
	public function getTimestamp() {
		if ( class_exists( 'MWTimestamp' ) ) {
			$timestamp = new MWTimestamp();
			return $timestamp->getTimestamp( TS_UNIX );
		} else {
			return wfTimestamp( TS_UNIX );
		}
	}

	/**
	 * Returns a CacheHandler instance
	 *
	 * @since 1.9
	 *
	 * @return CacheHandler
	 */
	public function getCache() {
		return CacheHandler::newFromId( $this->settings->get( 'smwgCacheType' ) );
	}

	/**
	 * Returns table definition for a given property type
	 *
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function getPropertyTables( $type, $dataItemId = true ) {

		$propertyTables = $this->store->getPropertyTables();

		if ( $dataItemId ) {
			$id = $this->store->findTypeTableId( $type );
		} else {
			$id = $this->store->findPropertyTableID( new DIProperty( $type ) );
		}

		return $propertyTables[$id];
	}
}
