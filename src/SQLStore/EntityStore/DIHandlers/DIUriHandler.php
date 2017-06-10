<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
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
		return array(
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => $this->getCharFieldType()
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return array(
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => $this->getCharFieldType()
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return array( 'o_serialized' => rawurldecode( $dataItem->getSerialization() ) );
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

		return array(
			'o_blob' => $text,
			'o_serialized' => $serialization,
		);
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

		return $length;
	}

	private function getCharFieldType() {

		$fieldType = FieldType::FIELD_TITLE;

		if ( $this->isEnabledFeature( SMW_FIELDT_CHAR_NOCASE ) ) {
			$fieldType = FieldType::TYPE_CHAR_NOCASE;
		}

		return $fieldType;
	}

}
