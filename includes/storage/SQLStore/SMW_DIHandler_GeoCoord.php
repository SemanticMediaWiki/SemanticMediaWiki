<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements store access to SMWDIGeoCoord data items.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerGeoCoord extends SMWDataItemHandler {

	/**
	 * Coordinates have three fields: a string version to keep the
	 * serialized value (exact), and two floating point columns for
	 * latitude and longitude (inexact, useful for bounding box selects).
	 * Altitude is not stored in an extra column since no operation uses
	 * this for anything so far.
	 *
	 * @see SMWDataItemHandler::getTableFields()
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_serialized' => 't', 'o_lat' => 'f', 'o_lon' => 'f' );
	}

	/**
	 * @see SMWDataItemHandler::getFetchFields()
	 *
	 * @since 1.8
	 * @return array
	 */
	public function getFetchFields() {
		return array( 'o_serialized' => 't' );
	}

	/**
	 * @see SMWDataItemHandler::getTableIndexes()
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'o_lat,o_lon' );
	}

	/**
	 * @see SMWDataItemHandler::getWhereConds()
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array(
			'o_serialized' => $dataItem->getSerialization()
		);
	}

	/**
	 * @see SMWDataItemHandler::getInsertValues()
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		return array(
			'o_serialized' => $dataItem->getSerialization(),
			'o_lat' => $dataItem->getLatitude(),
			'o_lon' => $dataItem->getLongitude()
		);
	}

	/**
	 * @see SMWDataItemHandler::getIndexField()
	 * @return string
	 */
	public function getIndexField() {
		return 'o_serialized';
	}

	/**
	 * Coordinates do not have a general string version that
	 * could be used for string search, so this method returns
	 * no label column (empty string).
	 *
	 * @see SMWDataItemHandler::getLabelField()
	 * @since 1.8
	 * @return string
	 */
	public function getLabelField() {
		return '';
	}

	/**
	 * @see SMWDataItemHandler::dataItemFromDBKeys()
	 * @since 1.8
	 * @param array|string $dbkeys expecting string here
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		if ( is_string( $dbkeys ) ) {
			return SMWDIGeoCoord::doUnserialize( $dbkeys );
		} else {
			throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
		}
	}
}
