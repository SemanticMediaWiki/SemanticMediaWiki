<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Concept data items.
 *
 * @since SMW.storerewrite
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerConcept implements SMWDataItemHandler {

	/**
	 * Method to return array of fields and indexes for a DI type
	 *
	 * @since SMW.storerewrite
	 *
	 * @return array
	 */
	public function getTableFields(){
		return array(
			'objectfields' => array(
				'concept_txt' => 'l',
				'concept_docu' => 'l',
				'concept_features' => 'n',
				'concept_size' => 'n',
				'concept_depth' => 'n',
				'cache_date' => 'j',
				'cache_count' => 'j'
			),
			'indexes' => array(),
		);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @since SMW.storerewrite
	 *
	 * @param SMWDataItem $dataItem
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		return array(
			'concept_txt' => $dataItem->getConceptQuery(),
			'concept_docu' => $dataItem->getDocumentation(),
			'concept_features' => $dataItem->getQueryFeatures(),
			'concept_size' => $dataItem->getSize(),
			'concept_depth' => $dataItem->getDepth()
		);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 * To optimize return minimum fields having indexes
	 *
	 * @since SMW.storerewrite
	 *
	 * @param SMWDataItem $dataItem
	 *
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		return array(
			'concept_txt' => $dataItem->getConceptQuery(),
			'concept_docu' => $dataItem->getDocumentation(),
			'concept_features' => $dataItem->getQueryFeatures(),
			'concept_size' => $dataItem->getSize(),
			'concept_depth' => $dataItem->getDepth()
		);
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getIndexField() {
		return 'concept_txt';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since SMW.storerewrite
	 * @return string
	 */
	public function getLabelField() {
		return 'concept_txt';
	}

	/**
	 * Method to create a dataitem from a type ID and array of DB keys.
	 *
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $typeId, $dbkeys ) {
		if ( count( $dbkeys ) >= 5 ) {
			return new SMWDIConcept( $dbkeys[0], smwfXMLContentEncode( $dbkeys[1] ),
				$dbkeys[2], $dbkeys[3], $dbkeys[4] );
		}
		throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
	}
}
