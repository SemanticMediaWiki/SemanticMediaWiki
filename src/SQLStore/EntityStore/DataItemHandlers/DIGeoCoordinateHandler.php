<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;
use SMWDIGeoCoord as DIGeoCoord;

/**
 * This class implements store access to DIGeoCoord data items.
 *
 * @note The table layout and behavior of this class is not coherent with the
 * way that other DIs work. This is because of the unfortunate use of the
 * concept table to store extra cache data, but also due to the design of
 * concept DIs. This will be cleaned up at some point.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DIGeoCoordinateHandler extends DataItemHandler {

	/**
	 * Coordinates have three fields: a string version to keep the
	 * serialized value (exact), and two floating point columns for
	 * latitude and longitude (inexact, useful for bounding box selects).
	 * Altitude is not stored in an extra column since no operation uses
	 * this for anything so far.
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return [
			'o_serialized' => FieldType::FIELD_TITLE,
			'o_lat' => FieldType::TYPE_DOUBLE,
			'o_lon' => FieldType::TYPE_DOUBLE
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return [
			'o_serialized' => FieldType::FIELD_TITLE
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes() {
		return [
			'p_id,o_serialized',
			'o_lat,o_lon'
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return [
			'o_serialized' => $dataItem->getSerialization()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		return [
			'o_serialized' => $dataItem->getSerialization(),
			'o_lat' => (string)$dataItem->getLatitude(),
			'o_lon' => (string)$dataItem->getLongitude()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField() {
		return 'o_serialized';
	}

	/**
	 * Coordinates do not have a general string version that
	 * could be used for string search, so this method returns
	 * no label column (empty string).
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField() {
		return '';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ) {

		if ( is_string( $dbkeys ) ) {
			return DIGeoCoord::doUnserialize( $dbkeys );
		}

		throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
	}

}
