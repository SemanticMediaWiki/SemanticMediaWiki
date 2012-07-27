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
	 * Method to return array of fields for a DI type
	 *
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_id' => 'p' );
	}

	/**
	 * Method to return array of indexes for a DI type
	 *
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'o_id' );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * To optimize return minimum fields having indexes
	 *
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		$subject = $dataItem->getSemanticData()->getSubject();
		$sid = smwfGetStore()->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(), $subject->getSubobjectName() );
		return array( 'o_id' => $sid );
	}

	/**
	 * Method to return an array of fields=>values for a DataItem
	 * This array is used to perform all insert operations into the DB
	 *
	 * NOTE - This only writes the id of a container.
	 * Calling code needs to handle the rest of the container by recursion.
	 * It could resue the $sid returned here rather than getting a new one.
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		$subject = $dataItem->getSemanticData()->getSubject();
		$sid = smwfGetStore()->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(), $subject->getInterwiki(),
			$subject->getSubobjectName(), true, str_replace( '_', ' ', $subject->getDBkey() ) . $subject->getSubobjectName() );
		return array( 'o_id' => $sid );
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
	 * Method to create a dataitem from an array of DB keys.
	 *
	 * @since SMW.storerewrite
	 * @param $dbkeys array of mixed
	 *
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		$diHandler = SMWDIHandlerFactory::getDataItemHandlerForDIType( SMWDataItem::TYPE_WIKIPAGE );
		$diSubWikiPage = $diHandler->dataItemFromDBKeys( $dbkeys );
		$semanticData = new SMWContainerSemanticData( $diSubWikiPage );
		$semanticData->copyDataFrom( smwfGetStore()->getSemanticData( $diSubWikiPage ) );
		return new SMWDIContainer( $semanticData );
	}
}
