<?php
/**
 * @file
 * @ingroup SMWDataItemsHandlers
 */

/**
 * This class implements Store access to Concept data items.
 *
 * @note The table layout and behaviour of this class is not coherent with the
 * way that other DIs work. This is because of the unfortunate use of the
 * concept table to store extra cache data, but also due to the design of
 * concept DIs. This will be cleaned up at some point.
 *
 * @since 1.8
 *
 * @author Nischay Nahata
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerConcept extends SMWDataItemHandler {

	/**
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array(
				'concept_txt' => 'l',
				'concept_docu' => 'l',
				'concept_features' => 'n',
				'concept_size' => 'n',
				'concept_depth' => 'n',
				'cache_date' => 'j',
				'cache_count' => 'j'
			);
	}

	/**
	 * @see SMWDataItemHandler::getFetchFields()
	 *
	 * @since 1.8
	 * @return array
	 */
	public function getFetchFields() {
		return array(
				'concept_txt' => 'l',
				'concept_docu' => 'l',
				'concept_features' => 'n',
				'concept_size' => 'n',
				'concept_depth' => 'n'
			);
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 *
	 * @since 1.8
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
	 * @since 1.8
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
	 * @since 1.8
	 * @return string
	 */
	public function getIndexField() {
		return 'concept_txt';
	}

	/**
	 * Method to return the field used to select this type of DataItem
	 * using the label
	 * @since 1.8
	 * @return string
	 */
	public function getLabelField() {
		return 'concept_txt';
	}

	/**
	 * Method to create a dataitem from an array of DB keys.
	 *
	 * @param array|string $dbkeys expecting array here
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		if ( is_array( $dbkeys) && count( $dbkeys ) == 5 ) {
			return new SMWDIConcept( $dbkeys[0], smwfXMLContentEncode( $dbkeys[1] ),
				$dbkeys[2], $dbkeys[3], $dbkeys[4] );
		} else {
			throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
		}
	}
}
