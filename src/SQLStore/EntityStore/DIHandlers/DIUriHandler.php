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

	const MAX_LENGTH = 255;

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return array(
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => FieldType::FIELD_TITLE
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
			'o_serialized' => FieldType::FIELD_TITLE
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
		$text = mb_strlen( $serialization ) <= self::MAX_LENGTH ? null : $serialization;

		// bytea type handling
		if ( $text !== null && $GLOBALS['wgDBtype'] === 'postgres' ) {
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

		if ( $GLOBALS['wgDBtype'] === 'postgres' ) {
			$dbkeys[0] = pg_unescape_bytea( $dbkeys[0] );
		}

		return DIUri::doUnserialize( $dbkeys[0] == '' ? $dbkeys[1] : $dbkeys[0] );
	}

}
