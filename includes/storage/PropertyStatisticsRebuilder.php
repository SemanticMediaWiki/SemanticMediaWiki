<?php

namespace SMW\Store;
use MWException;

/**
 * Interface for PropertyStatisticsStore rebuilders.
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
 * @ingroup SMWStore
 *
 * @license GNU GPL v2 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface PropertyStatisticsRebuilder {

	/**
	 * @since 1.9
	 *
	 * @param PropertyStatisticsStore $propStatsStore
	 * @param \DatabaseBase $dbw
	 *
	 * TODO: pass in store instead of DatabaseBase
	 */
	public function rebuild( PropertyStatisticsStore $propStatsStore, \DatabaseBase $dbw );

}
