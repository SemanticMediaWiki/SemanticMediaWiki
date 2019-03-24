<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;
use SMWDIUri as DIUri;

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
		return [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => $this->getCharFieldType()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => $this->getCharFieldType()
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
		];
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getIndexHint( $key ) {

		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids`
		// INNER JOIN `smw_di_uri` AS t1
		// FORCE INDEX(s_id) ON t1.s_id=smw_id
		// WHERE t1.p_id='310165' AND smw_iw!=':smw' AND smw_iw!=':smw-delete' AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id LIMIT 26
		//
		// 606.8370ms SMWSQLStore3Readers::getPropertySubjects
		//
		// vs.
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids`
		// INNER JOIN `smw_di_uri` AS t1 ON t1.s_id=smw_id
		// WHERE t1.p_id='310165' AND smw_iw!=':smw' AND smw_iw!=':smw-delete' AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id LIMIT 26
		//
		// 8052.2099ms SMWSQLStore3Readers::getPropertySubjects
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
		return [ 'o_serialized' => rawurldecode( $dataItem->getSerialization() ) ];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {

		$serialization = rawurldecode( $dataItem->getSerialization() );
		$text = mb_strlen( $serialization ) <= $this->getMaxLength() ? null : $serialization;

		// bytea type handling
		if ( $text !== null && $this->isDbType( 'postgres' ) ) {
			$text = pg_escape_bytea( $text );
		}

		return [
			'o_blob' => $text,
			'o_serialized' => $serialization,
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

		if ( !is_array( $dbkeys ) || count( $dbkeys ) != 2 ) {
			throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
		}

		if ( $this->isDbType( 'postgres' ) ) {
			$dbkeys[0] = pg_unescape_bytea( $dbkeys[0] );
		}

		return DIUri::doUnserialize( $dbkeys[0] == '' ? $dbkeys[1] : $dbkeys[0] );
	}

	private function getMaxLength() {

		$length = 255;

		if ( $this->hasFeature( SMW_FIELDT_CHAR_LONG ) ) {
			$length = FieldType::CHAR_LONG_LENGTH;
		}

		return $length;
	}

	private function getCharFieldType() {

		$fieldType = FieldType::FIELD_TITLE;

		if ( $this->hasFeature( SMW_FIELDT_CHAR_NOCASE ) ) {
			$fieldType = FieldType::TYPE_CHAR_NOCASE;
		}

		if ( $this->hasFeature( SMW_FIELDT_CHAR_LONG ) ) {
			$fieldType = FieldType::TYPE_CHAR_LONG;
		}

		if ( $this->hasFeature( SMW_FIELDT_CHAR_LONG ) && $this->hasFeature( SMW_FIELDT_CHAR_NOCASE ) ) {
			$fieldType = FieldType::TYPE_CHAR_LONG_NOCASE;
		}

		return $fieldType;
	}

}
