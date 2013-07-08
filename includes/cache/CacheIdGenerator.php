<?php

namespace SMW;

/**
 * This class is responsible for generating a cache key
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
 * This class is responsible for generating a cache key
 *
 * @ingroup Cache
 */
class CacheIdGenerator extends HashIdGenerator {

	/**
	 * Returns a prefix
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix === null ? $this->buildPrefix( 'smw' ) : $this->buildPrefix( 'smw' . ':' . $this->prefix );
	}

	/**
	 * Builds a prefix string
	 *
	 * @note Somehow eliminate the global function wfWikiID
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function buildPrefix( $prefix ) {
		$CachePrefix = $GLOBALS['wgCachePrefix'] === false ? wfWikiID() : $GLOBALS['wgCachePrefix'];
		return $CachePrefix . ':' . $prefix . ':';
	}

}
