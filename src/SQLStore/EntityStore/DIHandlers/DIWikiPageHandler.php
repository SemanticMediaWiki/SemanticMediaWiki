<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\TableBuilder\FieldType;

/**
 * DataItemHandler for dataitems of type DIWikiPage.
 *
 * This handler is slightly different from other handlers since wikipages are
 * stored in a separate table and referred to by numeric IDs. The handler thus
 * returns IDs in most cases, but expects data from the SMW IDs table (with
 * DBkey, namespace, interwiki, subobjectname) to be given for creating new
 * dataitems. The store recognizes this special behavior from the field type
 * 'p' that the handler reports for its only data field.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 * @author Markus Kroetzsch
 */
class DIWikiPageHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return array( 'o_id' => FieldType::FIELD_ID );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return array( 'o_id' => FieldType::FIELD_ID );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes() {
		return array( 'o_id' );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {

		$oid = $this->store->getObjectIds()->getSMWPageID(
			$dataItem->getDBkey(),
			$dataItem->getNamespace(),
			$dataItem->getInterwiki(),
			$dataItem->getSubobjectName()
		);

		return array( 'o_id' => $oid );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {

		$oid = $this->store->getObjectIds()->makeSMWPageID(
			$dataItem->getDBkey(),
			$dataItem->getNamespace(),
			$dataItem->getInterwiki(),
			$dataItem->getSubobjectName()
		);

		return array( 'o_id' => $oid );
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField() {
		return 'o_id';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField() {
		return 'o_id';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ) {
		if ( is_array( $dbkeys ) && count( $dbkeys ) == 5 ) {
			$namespace = intval( $dbkeys[1] );

			if ( $namespace == SMW_NS_PROPERTY && $dbkeys[0] != '' &&
				$dbkeys[0]{0} == '_' && $dbkeys[2] == '' ) {
				// Correctly interpret internal property keys
				$property = new DIProperty( $dbkeys[0] );
				$wikipage = $property->getCanonicalDiWikiPage( $dbkeys[4] );
				if ( !is_null( $wikipage ) ) {
					return $wikipage;
				}
			} else {
				return $this->newDiWikiPage( $dbkeys );
			}
		}

		throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
	}

	private function newDiWikiPage( $dbkeys ) {

		$diWikiPage = new DIWikiPage(
			$dbkeys[0],
			intval( $dbkeys[1] ),
			$dbkeys[2],
			$dbkeys[4]
		);

		$diWikiPage->setSortKey( $dbkeys[3] );

		return $diWikiPage;
	}

}
