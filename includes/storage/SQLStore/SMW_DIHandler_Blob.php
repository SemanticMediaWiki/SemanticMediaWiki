<?php
/**
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to blob (string) data items.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerBlob extends SMWDataItemHandler {

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
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_blob' => 'l', 'o_hash' => 't' );
	}

	/**
	 * @see SMWDataItemHandler::getFetchFields()
	 *
	 * @since 1.8
	 * @return array
	 */
	public function getFetchFields() {
		return array( 'o_blob' => 'l', 'o_hash' => 't' );
	}

	/**
	 * @see SMWDataItemHandler::getTableIndexes
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 's_id,o_hash' );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array( 'o_hash' => self::makeHash( $dataItem->getString() ) );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		$text = $dataItem->getString();
		return array(
			'o_blob' => strlen( $text ) <= self::MAX_HASH_LENGTH ? null : $text,
			'o_hash' => self::makeHash( $text ),
		);
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since 1.8
	 * @return string
	 */
	public function getIndexField() {
		return 'o_hash';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since 1.8
	 * @return string
	 */
	public function getLabelField() {
		return 'o_hash';
	}

	/**
	 * @see SMWDataItemHandler::dataItemFromDBKeys()
	 * @since 1.8
	 * @param array|string $dbkeys expecting array here
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		if ( !is_array( $dbkeys ) || count( $dbkeys ) != 2 ) {
			throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
		}
		if ( $dbkeys[0] == '' ) { // empty blob: use "hash" string
			return new SMWDIBlob( $dbkeys[1] );
		} else {
			return new SMWDIBlob( $dbkeys[0] );
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
