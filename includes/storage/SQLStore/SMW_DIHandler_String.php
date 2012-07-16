<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to String data items.
 *
 * @since SMW.storerewrite
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerString implements SMWDataItemHandler {

	/**
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'value_blob' => 'l', 'value_hash' => 't' );
	}

	/**
	 * Method to return array of indexes for a DI type
	 *
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'value_hash' );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array( 'value_hash' => self::makeHash( $dataItem->getString() ) );
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
			'value_blob' => strlen( $text ) <= 255 ? '' : $text,
			'value_hash' => self::makeHash( $text ),
		);
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getIndexField() {
		return 'value_hash';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getLabelField() {
		//What is the best choice to return here?
		return 'value_hash';
	}

	/**
	 * Method to create a dataitem from a type ID and array of DB keys.
	 *
	 * @since SMW.storerewrite
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $typeId, $dbkeys ) {
		$text = $dbkeys[0] == '' ? $dbkeys[1] : $dbkeys[0];
		return new SMWDIString( $text );
	}

	/**
	* Method to make a hashed representation for strings of length>255
	* to be used for selecting and sorting
	*
	* @since SMW.storerewrite
	* @param $string string
	*
	* @return string
	*/
	static protected function makeHash( $string ) {
		if( strlen( $string ) <= 255 ) {
			return $string;
		} else {
			return substr( $string, 0, 254 ) . md5( $string );
		}
	}
}
