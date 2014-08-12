<?php
/**
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
	 * @see SMWDataItemHandler::getFetchFields()
	 *
	 * @since 1.8
	 * @return array
	 */
	public function getFetchFields() {
		return array( 'o_serialized' => 't' );
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
	 * @see SMWDataItemHandler::dataItemFromDBKeys()
	 * @since 1.8
	 * @param array|string $dbkeys expecting string here
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		if ( is_string( $dbkeys ) ) {
			return SMWDINumber::doUnserialize( $dbkeys );
		} else {
			throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
		}
	}
}
