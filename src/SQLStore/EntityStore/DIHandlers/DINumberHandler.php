<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exceptions\DataItemHandlerException;
use SMWDINumber as DINumber;

/**
 * This class implements Store access to Number data items.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DINumberHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return array( 'o_serialized' => 't', 'o_sortkey' => 'f' );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return array( 'o_serialized' => 't' );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return array(
			'o_sortkey' => floatval( $dataItem->getNumber() )
			);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		return array(
			'o_serialized' => $dataItem->getSerialization(),
			'o_sortkey' => floatval( $dataItem->getNumber() )
			);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField() {
		return 'o_sortkey';
	}

	/**
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
			return DINumber::doUnserialize( $dbkeys );
		}

		throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
	}
}
