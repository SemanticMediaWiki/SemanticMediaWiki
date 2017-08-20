<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMWDINumber as DINumber;
use SMW\SQLStore\TableBuilder\FieldType;

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
		return array(
			'o_serialized' => FieldType::FIELD_TITLE,
			'o_sortkey' => FieldType::TYPE_DOUBLE
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return array(
			'o_serialized' => FieldType::FIELD_TITLE
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes() {
		return array(
			// QueryEngine::getInstanceQueryResult
			's_id,p_id,o_sortkey',

			// QueryEngine::getInstanceQueryResult
			// `smw_di_number` AS t5 ON t2.s_id=t5.s_id WHERE ... ((t5.o_sortkey>='700') AND t5.p_id='120') ORDER BY ... t5.o_sortkey
			'o_sortkey,p_id,s_id',

			// SMWSQLStore3Readers::getPropertySubjects
			'p_id,s_id'
		);
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
