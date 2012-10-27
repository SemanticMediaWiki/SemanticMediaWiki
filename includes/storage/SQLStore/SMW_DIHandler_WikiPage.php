<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Time data items.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerWikiPage extends SMWDataItemHandler {

	/**
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_id' => 'p' );
	}

	/**
	 * Create an additional index for finding incoming properties.
	 *
	 * @see SMWDataItemHandler::getTableIndexes()
	 * @since 1.8
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'o_id' );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $di) {
		$oid = $this->store->smwIds->getSMWPageID( $di->getDBkey(), $di->getNamespace(), $di->getInterwiki(), $di->getSubobjectName() );
		return array( 'o_id' => $oid );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $di ) {
		$oid = $this->store->smwIds->makeSMWPageID( $di->getDBkey(), $di->getNamespace(), $di->getInterwiki(), $di->getSubobjectName() );
		return array( 'o_id' => $oid );
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * Take care we are returning the field from the ID table, so do a proper JOIN
	 * @since 1.8
	 * @return string
	 */
	public function getIndexField() {
		return 'o_id';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label. Take care we are returning the field from the 
	 * ID table, so do a proper JOIN
	 * @since 1.8
	 * @return string
	 */
	public function getLabelField() {
		return 'o_id';
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
		if ( count( $dbkeys ) == 5 ) {
			return new SMWDIWikiPage( $dbkeys[0], intval( $dbkeys[1] ), $dbkeys[2], $dbkeys[4] );
		} else {
			throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
		}
	}
}
