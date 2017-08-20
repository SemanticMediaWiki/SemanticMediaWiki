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
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return array(
			'o_blob' => FieldType::TYPE_BLOB,
			'o_hash' => $this->getCharFieldType()
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
			'o_hash' => $this->getCharFieldType()
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes() {
		return array(

			's_id,o_hash',

			//SMW\SQLStore\QueryEngine\QueryEngine::getInstanceQueryResult
			'o_hash,p_id',

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
		return array( 'o_hash' => $this->makeHash( $dataItem->getString() ) );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {

		$text = htmlspecialchars_decode( trim( $dataItem->getString() ), ENT_QUOTES );
		$hash = $this->makeHash( $text );

		if ( $this->isDbType( 'postgres' ) ) {
			$text = pg_escape_bytea( $text );
		}

		if ( mb_strlen( $text ) <= $this->getMaxLength() ) {
			$text = null;
		}

		return array(
			'o_blob' => $text,
			'o_hash' => $hash,
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

		if ( $this->isDbType( 'postgres' ) ) {
			$dbkeys[0] = pg_unescape_bytea( $dbkeys[0] );
		}

		// empty blob: use "hash" string
		if ( $dbkeys[0] == '' ) {
			return new DIBlob( $dbkeys[1] );
		}

		return new DIBlob( $dbkeys[0] );
	}

	/**
	* Method to make a hashed representation for strings of length greater
	* than DIBlobHandler::getMaxLength to be used for selecting and sorting.
	*
	* @since 1.8
	* @param $string string
	*
	* @return string
	*/
	private function makeHash( $string ) {

		$length = $this->getMaxLength();

		if( mb_strlen( $string ) <= $length ) {
			return $string;
		}

		return mb_substr( $string, 0, $length - 32 ) . md5( $string );
	}

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
	 *
	 * Using `SMW_FIELDT_CHAR_LONG` as option in `smwgFieldTypeFeatures`
	 * will extend the field size to 300 and expands the maximum matchable
	 * string length to 300-32 for LIKE/NLIKE queries.
	 *
	 * @since 3.0
	 */
	private function getMaxLength() {

		$length = 72;

		if ( $this->isEnabledFeature( SMW_FIELDT_CHAR_LONG ) ) {
			$length = FieldType::CHAR_LONG_LENGTH;
		}

		return $length;
	}

	private function getCharFieldType() {

		$fieldType = FieldType::FIELD_TITLE;

		if ( $this->isEnabledFeature( SMW_FIELDT_CHAR_NOCASE ) ) {
			$fieldType = FieldType::TYPE_CHAR_NOCASE;
		}

		if ( $this->isEnabledFeature( SMW_FIELDT_CHAR_LONG ) ) {
			$fieldType = FieldType::TYPE_CHAR_LONG;
		}

		if ( $this->isEnabledFeature( SMW_FIELDT_CHAR_LONG ) && $this->isEnabledFeature( SMW_FIELDT_CHAR_NOCASE ) ) {
			$fieldType = FieldType::TYPE_CHAR_LONG_NOCASE;
		}

		return $fieldType;
	}

}
