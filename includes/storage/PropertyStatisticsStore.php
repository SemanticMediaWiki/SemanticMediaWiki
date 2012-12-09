<?php

namespace SMW\Store;

/**
 * Interface for stores of property statistics.
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
interface PropertyStatisticsStore {

	/**
	 * Change the usage count for the property of the given ID by the given
	 * value. The method does nothing if the count is 0.
	 *
	 * @since 1.9
	 *
	 * @param integer $propertyId
	 * @param integer $value
	 *
	 * @return boolean Success indicator
	 */
	public function addToUsageCount( $propertyId, $value );

	/**
	 * Increase the usage counts of multiple properties.
	 *
	 * The $additions parameter should be an array with integer
	 * keys that are property ids, and associated integer values
	 * that are the amount the usage count should be increased.
	 *
	 * @since 1.9
	 *
	 * @param array $additions
	 *
	 * @return boolean Success indicator
	 */
	public function addToUsageCounts( array $additions );

	/**
	 * Updates an existing usage count.
	 *
	 * @since 1.9
	 *
	 * @param integer $propertyId
	 * @param integer $value
	 *
	 * @return boolean Success indicator
	 */
	public function setUsageCount( $propertyId, $value );

	/**
	 * Adds a new usage count.
	 *
	 * @since 1.9
	 *
	 * @param integer $propertyId
	 * @param integer $value
	 *
	 * @return boolean Success indicator
	 */
	public function insertUsageCount( $propertyId, $value );

	/**
	 * Returns the usage counts of the provided properties.
	 *
	 * The returned array contains integer keys which are property ids,
	 * with the associated values being their usage count (also integers).
	 *
	 * Properties for which no usage count is found will not have
	 * an entry in the result array.
	 *
	 * @since 1.9
	 *
	 * @param array $propertyIds
	 *
	 * @return array
	 */
	public function getUsageCounts( array $propertyIds );

	/**
	 * Deletes all rows in the table.
	 *
	 * @since 1.9
	 *
	 * @return boolean Success indicator
	 */
	public function deleteAll();

}