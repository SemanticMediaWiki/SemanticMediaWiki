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
		return array( 'serialized' => 't', 'lat' => 'f', 'lon' => 'f' );
	}

	/**
	 * @see SMWDataItemHandler::getTableIndexes()
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'serialized', 'lat', 'lon' );
	}

	/**
	 * @see SMWDataItemHandler::getWhereConds()
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array(
			'serialized' => $dataItem->getSerialization()
		);
	}

	/**
	 * @see SMWDataItemHandler::getInsertValues()
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		return array(
			'serialized' => $dataItem->getSerialization(),
			'lat' => $dataItem->getLatitude(),
			'lon' => $dataItem->getLongitude()
		);
	}

	/**
	 * @see SMWDataItemHandler::getIndexField()
	 * @return string
	 */
	public function getIndexField() {
		return 'serialized';
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
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		return SMWDIGeoCoord::doUnserialize( $dbkeys[0] );
	}
}
