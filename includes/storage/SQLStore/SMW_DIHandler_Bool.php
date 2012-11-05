<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Boolean data items.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerBoolean extends SMWDataItemHandler {

	/**
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_value' => 'b' );
	}

	/**
	 * @see SMWDataItemHandler::getFetchFields()
	 *
	 * @since 1.8
	 * @return array
	 */
	public function getFetchFields() {
		return array( 'o_value' => 'b' );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array(
			'o_value' => $dataItem->getBoolean() ? 1 : 0,
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
			'o_value' => $dataItem->getBoolean() ? 1 : 0,
		);
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since 1.8
	 * @return string
	 */
	public function getIndexField() {
		return 'o_value';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since 1.8
	 * @return string
	 */
	public function getLabelField() {
		return 'o_value';
	}

	/**
	 * Method to create a dataitem from an array of DB keys.
	 *
	 * @since 1.8
	 * @param array|string $dbkeys should be a string here
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		global $wgDBtype;

		//PgSQL returns as t and f and need special handling http://archives.postgresql.org/pgsql-php/2010-02/msg00005.php
		if ( $wgDBtype == 'postgres' ) {
			$value = ( $dbkeys == 't' );
		} else {
			$value = ( $dbkeys == '1' );
		}

		return new SMWDIBoolean( $value );
	}
}
