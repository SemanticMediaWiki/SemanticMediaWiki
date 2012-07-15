<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Container data items.
 *
 * @since SMW.storerewrite
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerContainer implements SMWDataItemHandler {

	/**
	 * Method to return array of fields and indexes for a DI type
	 *
	 * @return array
	 */
	public function getTableFields(){
		return array(
			'objectfields' => array( 'o_id' => 'p' ),
			'indexes' => array( 'o_id' ),
		);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array( false );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		return array( false );
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getIndexField() {
		return '';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getLabelField() {
		return '';
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
		// provided for backwards compatibility only;
		// today containers are read from the store as substructures,
		// not retrieved as single complex values
		$semanticData = SMWContainerSemanticData::makeAnonymousContainer();
		foreach ( reset( $dbkeys ) as $value ) {
			if ( is_array( $value ) && ( count( $value ) == 2 ) ) {
				$diProperty = new SMWdiPropertyroperty( reset( $value ), false );
				$diHandler = SMWDIHandlerFactory::getDataItemHandlerForDIType( $diProperty->getDIType() );
				$diValue= $diHandler->dataItemFromDBKeys( $diProperty->findPropertyTypeID(), end( $value ) );
				$semanticData->addPropertyObjectValue( $diProperty, $diValue );
			}
		}
		return new SMWDIContainer( $semanticData );
	}
}
