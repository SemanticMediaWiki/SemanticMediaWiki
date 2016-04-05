<?php
/**
 * @author Nischay Nahata
 * @author Markus Kroetzsch
 * @ingroup SMWDataItemsHandlers
 */

/**
 * SMWDataItemHandler for dataitems of type SMWDIWikiPage.
 *
 * This handler is slightly different from other handlers since wikipages are
 * stored in a separate table and referred to by numeric IDs. The handler thus
 * returns IDs in most cases, but expects data from the SMW IDs table (with
 * DBkey, namespace, interwiki, subobjectname) to be given for creating new
 * dataitems. The store recognizes this special behavior from the field type
 * 'p' that the handler reports for its only data field.
 *
 * @since 1.8
 * @ingroup SMWDataItemsHandlers
 */
class SMWDIHandlerWikiPage extends SMWDataItemHandler {

	/**
	 * @see SMWDataItemHandler::getTableFields()
	 * @since 1.8
	 * @return array
	 */
	public function getTableFields() {
		return array( 'o_id' => 'p' );
	}

	/**
	 * @see SMWDataItemHandler::getFetchFields()
	 * @since 1.8
	 * @return array
	 */
	public function getFetchFields() {
		return array( 'o_id' => 'p' );
	}

	/**
	 * @see SMWDataItemHandler::getTableIndexes()
	 * @since 1.8
	 * @return array
	 */
	public function getTableIndexes() {
		return array( 'o_id' );
	}

	/**
	 * @see SMWDataItemHandler::getWhereConds()
	 * @since 1.8
	 * @param SMWDataItem $dataItem
	 * @return array
	 */
	public function getWhereConds( SMWDataItem $dataItem ) {
		$oid = $this->store->smwIds->getSMWPageID(
				$dataItem->getDBkey(),
				$dataItem->getNamespace(),
				$dataItem->getInterwiki(),
				$dataItem->getSubobjectName()
			);
		return array( 'o_id' => $oid );
	}

	/**
	 * @see SMWDataItemHandler::getInsertValues()
	 * @since 1.8
	 * @param SMWDataItem $dataItem
	 * @return array
	 */
	public function getInsertValues( SMWDataItem $dataItem ) {
		$oid = $this->store->smwIds->makeSMWPageID(
				$dataItem->getDBkey(),
				$dataItem->getNamespace(),
				$dataItem->getInterwiki(),
				$dataItem->getSubobjectName()
			);
		return array( 'o_id' => $oid );
	}

	/**
	 * @see SMWDataItemHandler::getIndexField()
	 * @since 1.8
	 * @return string
	 */
	public function getIndexField() {
		return 'o_id';
	}

	/**
	 * @see SMWDataItemHandler::getLabelField()
	 * @since 1.8
	 * @return string
	 */
	public function getLabelField() {
		return 'o_id';
	}

	/**
	 * @see SMWDataItemHandler::dataItemFromDBKeys()
	 * @since 1.8
	 * @param array|string $dbkeys expecting array here
	 * @throws SMWDataItemException
	 * @return SMWDataItem
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		if ( is_array( $dbkeys ) && count( $dbkeys ) == 5 ) {
			$namespace = intval( $dbkeys[1] );

			if ( $namespace == SMW_NS_PROPERTY && $dbkeys[0] != '' &&
				$dbkeys[0]{0} == '_' && $dbkeys[2] == '' ) {
				// Correctly interpret internal property keys
				$property = new SMW\DIProperty( $dbkeys[0] );
				$wikipage = $property->getDiWikiPage( $dbkeys[4] );
				if ( !is_null( $wikipage ) ) {
					return $wikipage;
				}
			} else {
				return $this->newDiWikiPage( $dbkeys );
			}
		}

		throw new SMWDataItemException( 'Failed to create data item from DB keys.' );
	}

	private function newDiWikiPage( $dbkeys ) {

		$diWikiPage = new SMWDIWikiPage(
			$dbkeys[0],
			intval( $dbkeys[1] ),
			$dbkeys[2],
			$dbkeys[4]
		);

		$diWikiPage->setSortKey( $dbkeys[3] );

		return $diWikiPage;
	}

}
