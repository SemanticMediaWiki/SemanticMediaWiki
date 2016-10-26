<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exceptions\DataItemHandlerException;
use SMWDIUri as DIUri;
use SMW\SQLStore\TableBuilder\FieldType;

/**
 * This class implements Store access to Uri data items.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DIUriHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return array( 'o_serialized' => FieldType::FIELD_TITLE );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return array( 'o_serialized' => FieldType::FIELD_TITLE );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return array( 'o_serialized' => $dataItem->getSerialization() );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		return array( 'o_serialized' => $dataItem->getSerialization() );
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
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField() {
		return 'o_serialized';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ) {

		if ( is_string( $dbkeys ) ) {
			return DIUri::doUnserialize( $dbkeys );
		}

		throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
	}

}
