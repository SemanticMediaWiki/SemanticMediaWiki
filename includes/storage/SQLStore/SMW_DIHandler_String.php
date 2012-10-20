<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to String data items.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerString extends SMWDataItemHandler {

	/**
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_blob' => 'l', 'o_hash' => 't' );
	}

	/**
	 * Method to return array of indexes for a DI type
	 *
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'o_hash' );
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
			'o_blob' => strlen( $text ) <= 255 ? null : $text,
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
	 * Method to create a dataitem from an array of DB keys.
	 *
	 * @since 1.8
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		if ( $dbkeys[0] == '' ) { // empty blob: use "hash" string
			if ( count( $dbkeys ) == 2 ) {
				return new SMWDIString( $dbkeys[1] );
			} else {
				throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
			}
		} else { // use blob
			return new SMWDIString( $dbkeys[0] );
		}
	}

	/**
	* Method to make a hashed representation for strings of length>255
	* to be used for selecting and sorting
	*
	* @since 1.8
	* @param $string string
	*
	* @return string
	*/
	static protected function makeHash( $string ) {
		if( strlen( $string ) <= 255 ) {
			return $string;
		} else {
			return substr( $string, 0, 255 - 32 ) . md5( $string );
		}
	}
}
