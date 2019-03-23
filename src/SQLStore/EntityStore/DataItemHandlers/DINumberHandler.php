<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;
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

			// QueryEngine::getInstanceQueryResult
			's_id,p_id,o_sortkey',
		];
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getIndexHint( $key ) {

		// Store::getPropertySubjects has seen to choose the wrong index

		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids` INNER JOIN `smw_di_number` AS t1 FORCE INDEX(s_id) ON t1.s_id=smw_id
		// WHERE t1.p_id='310194' AND smw_iw!=':smw' AND smw_iw!=':smw-delete' AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id
		// LIMIT 26
		//
		// 584.9450ms SMWSQLStore3Readers::getPropertySubjects
		//
		// vs.
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids`
		// INNER JOIN `smw_di_number` AS t1 ON t1.s_id=smw_id
		// WHERE t1.p_id='310194' AND smw_iw!=':smw' AND smw_iw!=':smw-delete' AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id
		// LIMIT 26
		//
		// 21448.2622ms	SMWSQLStore3Readers::getPropertySubjects
		if ( $key === self::IHINT_PSUBJECTS && $this->isDbType( 'mysql' ) ) {
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
		return [
			'o_sortkey' => floatval( $dataItem->getNumber() )
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
			'o_sortkey' => floatval( $dataItem->getNumber() )
			];
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
		return 'o_serialized';
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
