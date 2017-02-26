<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMWDIBlob as DIBlob;
use SMW\SQLStore\TableBuilder\FieldType;

/**
 * This class implements Store access to blob (string) data items.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DIBlobHandler extends DataItemHandler {

	/**
	 * Maximal number of bytes (chars) to be stored in the hash field of
	 * the table. Must not be bigger than 255 (the length of our VARCHAR
	 * field in the DB). Strings that are longer than this will be stored
	 * as a blob, and the hash will only start with the original string
	 * but the last 32 bytes are used for a hash. So the minimal portion
	 * of the string that is stored literally in the hash is 32 chars
	 * less.
	 *
	 * The value of 72 was chosen since it leads to a smaller index size
	 * at the cost of needing more blobs in cases where many strings are
	 * of length 73 to 255. But keeping the index small seems more
	 * important than saving disk space. Also, with 72 bytes there are at
	 * least 40 bytes of content available for sorting and prefix matching,
	 * which should be more than enough in most contexts.
	 *
	 * @since 1.8
	 */
	const MAX_HASH_LENGTH = 72;

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return array(
			'o_blob' => FieldType::TYPE_BLOB,
			'o_hash' => FieldType::FIELD_TITLE
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
			'o_hash' => FieldType::FIELD_TITLE
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes() {
		return array( 's_id,o_hash' );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return array( 'o_hash' => self::makeHash( $dataItem->getString() ) );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		$text = htmlspecialchars_decode( trim( $dataItem->getString() ), ENT_QUOTES );

		return array(
			'o_blob' => strlen( $text ) <= self::MAX_HASH_LENGTH ? null : ( $GLOBALS['wgDBtype'] === 'postgres' ? pg_escape_bytea( $text ) : $text ),
			'o_hash' => self::makeHash( $text ),
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField() {
		return 'o_hash';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField() {
		return 'o_hash';
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

		if ( $dbkeys[0] == '' ) { // empty blob: use "hash" string
			return new DIBlob( $dbkeys[1] );
		} else {
			return new DIBlob( $dbkeys[0] );
		}
	}

	/**
	* Method to make a hashed representation for strings of length greater
	* than self::MAX_HASH_LENGTH to be used for selecting and sorting.
	*
	* @since 1.8
	* @param $string string
	*
	* @return string
	*/
	static protected function makeHash( $string ) {
		if( strlen( $string ) <= self::MAX_HASH_LENGTH ) {
			return $string;
		} else {
			return substr( $string, 0, self::MAX_HASH_LENGTH - 32 ) . md5( $string );
		}
	}
}
