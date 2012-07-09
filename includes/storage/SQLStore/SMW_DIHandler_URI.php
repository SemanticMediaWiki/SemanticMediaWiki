<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to URI data items.
 *
 * @since SMW.storerewrite
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerUri implements SMWDataItemHandler {

	/**
	 * Method to return array of fields and indexes for a DI type
	 *
	 * @return array
	 */
	public function getTableFields(){
		return array(
			'objectfields' => array( 'value_xsd' => 't', 'value_num' => 'f' ),
			'indexes' => array( 'value_num', 'value_xsd' ),
		);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array( 'value_xsd' => $dataItem->getSerialization() );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		//TODO - what to insert in value_num?? How was this done before?
		return array( 'value_xsd' => $dataItem->getSerialization() );
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getIndexField() {
		return 'value_num';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getLabelField() {
		return 'value_xsd';
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
		if ( $typeId == '__typ' && $dbkeys[0]{0} == '_' ) { // b/c: old data stored as type ids
			return SMWTypesValue::getTypeUriFromTypeId( $dbkeys[0] );
		} else {
			return SMWDIUri::doUnserialize( $dbkeys[0] );
		}
	}
}
