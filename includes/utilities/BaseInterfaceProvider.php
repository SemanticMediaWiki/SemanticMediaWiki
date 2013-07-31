<?php

namespace SMW;

/**
 * Semantic MediaWiki base class to enable access to commonly used objects
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
 * Specifies an interface to access a cachable entity (CacheStore etc.)
 *
 * @ingroup Utility
 */
interface Cacheable {

	/**
	 * Returns cachable entity
	 *
	 * @since 1.9
	 *
	 * @return Cacheable
	 */
	public function getCache();

}

/**
 * Specifies an interface to access a configurable object (Settings)
 *
 * @ingroup Utility
 */
interface Configurable {

	/**
	 * Sets a Settings object
	 *
	 * @since 1.9
	 *
	 * @param Settings $settings
	 */
	public function setSettings( Settings $settings );

	/**
	 * Returns Settings object
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public function getSettings();

}

/**
 * Specifies an interface to access a Store object
 *
 * @ingroup Utility
 */
interface StoreAccess {

	/**
	 * Sets a Store object
	 *
	 * @since 1.9
	 *
	 * @param Store $settings
	 */
	public function setStore( Store $store );

	/**
	 * Returns Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getStore();

}
