<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Number data items.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerNumber extends SMWDataItemHandler {

	/**
	 * @see SMWDataItemHandler::getTableFields()
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_serialized' => 't', 'o_sortkey' => 'f' );
	}

	/**
	 * @see SMWDataItemHandler::getWhereConds
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array(
			'o_sortkey' => floatval( $dataItem->getNumber() )
			);
	}

	/**
	 * @see SMWDataItemHandler::getInsertValues
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		return array(
			'o_serialized' => $dataItem->getSerialization(),
			'o_sortkey' => floatval( $dataItem->getNumber() )
			);
	}

	/**
	 * @see SMWDataItemHandler::getIndexField
	 * @return string
	 */
	public function getIndexField() {
		return 'o_sortkey';
	}

	/**
	 * @see SMWDataItemHandler::getLabelField
	 * @return string
	 */
	public function getLabelField() {
		return '';
	}

	/**
	 * @see SMWDataItemHandler::dataItemFromDBKeys
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		return SMWDINumber::doUnserialize( $dbkeys[0] );
	}
}
