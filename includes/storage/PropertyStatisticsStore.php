<?php

namespace SMW\Store;

/**
 * Interface for stores of property statistics.
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
	 * Returns the usage count for a provided property id.
	 *
	 * @since 2.2
	 *
	 * @param integer $propertyId
	 *
	 * @return integer
	 */
	public function getUsageCount( $propertyId );

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
