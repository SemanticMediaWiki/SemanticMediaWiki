<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Boolean data items.
 *
 * @since SMW.storerewrite
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerBoolean implements SMWDataItemHandler {

	/**
	 * Method to return array of fields and indexes for a DI type
	 *
	 * @return array
	 */
	public function getTableFields(){
		return array(
			'objectfields' => array( 'value_bool' => 'b' ),
			'indexes' => array( 'value_bool' ),
		);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array(
			'value_bool' => $dataItem->getBoolean() ? 1 : 0,
		);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		return array(
			'value_bool' => $dataItem->getBoolean() ? 1 : 0,
		);
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getIndexField() {
		return 'value_bool';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getLabelField() {
		return 'value_bool';
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
		global $wgDBtype;
		$value = true;

		//PgSQL returns as t and f and need special handling http://archives.postgresql.org/pgsql-php/2010-02/msg00005.php
		if ( $wgDBtype == 'postgres' ) {
			$value = $dbkeys[0] == 't' ;
		} else {
			$value = $dbkeys[0] == '1' ;
		}

		return new SMWDIBoolean( $value );
	}
}
