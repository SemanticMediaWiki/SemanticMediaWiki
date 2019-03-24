<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;
use SMWDITime as DITime;

/**
 * This class implements Store access to Time data items.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DITimeHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return [
			'o_serialized' => FieldType::FIELD_TITLE,
			'o_sortkey' => FieldType::TYPE_DOUBLE
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

			// API module pvalue lookup
			'p_id,o_serialized',
			'p_id,o_sortkey',

			// SMWSQLStore3Readers::fetchSemanticData
			// SELECT p.smw_title as prop,o_serialized AS v0, o_sortkey AS v2
			// FROM `smw_di_time` INNER JOIN `smw_object_ids` AS p ON
			// p_id=p.smw_id WHERE s_id='104822'	7.9291ms
			// ... FROM `smw_fpt_sobj` INNER JOIN `smw_object_ids` AS o0 ON
			// o_id=o0.smw_id WHERE s_id='104322'
			's_id,p_id,o_sortkey,o_serialized',
		];
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getIndexHint( $key ) {

		if ( 'property.subjects' && $this->isDbType( 'mysql' ) ) {
			return 's_id';
		}

		return '';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return [ 'o_sortkey' => $dataItem->getSortKey() ];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		return [
			'o_serialized' => $dataItem->getSerialization(),
			'o_sortkey' => $dataItem->getSortKey()
		];
	}

	/**
	 * This type is sorted by a numerical sortkey that maps time values to
	 * a time line.
	 *
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
		return 'o_serialized';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ) {

		if ( is_string( $dbkeys ) ) {
			return DITime::doUnserialize( $dbkeys );
		}

		throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
	}

}
