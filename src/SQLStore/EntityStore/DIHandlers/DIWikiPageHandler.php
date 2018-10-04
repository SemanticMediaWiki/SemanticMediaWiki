<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;

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
		return [ 'o_id' => FieldType::FIELD_ID ];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return [ 'o_id' => FieldType::FIELD_ID ];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes() {
		return [
			'o_id',

			// SMWSQLStore3Readers::getPropertySubjects
			'p_id,s_id',

			// SMWSQLStore3Readers::fetchSemanticData
			// ... FROM `smw_fpt_sobj` INNER JOIN `smw_object_ids` AS o0 ON
			// o_id=o0.smw_id WHERE s_id='104322'
			's_id,o_id',

			// SMWSQLStore3Readers::fetchSemanticData
			// ... FROM `smw_di_wikipage` INNER JOIN `smw_object_ids` AS p ON
			// p_id=p.smw_id INNER JOIN `smw_object_ids` AS o0 ON o_id=o0.smw_id
			// WHERE s_id='104815'
			's_id,p_id,o_id',

			// QueryEngine::getInstanceQueryResult
			// ... INNER JOIN `smw_fpt_inst` AS t3 ON t2.smw_id=t3.s_id WHERE
			// (t2.smw_namespace='0' AND (t3.o_id='56')
			'o_id,s_id',

			// QueryEngine::getInstanceQueryResult
			//'p_id,o_id,s_sort',

			// SMWSQLStore3Readers::getPropertySubjects
			// SELECT DISTINCT s_id FROM `smw_fpt_sobj` ORDER BY s_sort
			//'s_sort,s_id',

			// SELECT DISTINCT s_id FROM `smw_fpt_subp` WHERE o_id='96' ORDER BY s_sort
			//'o_id,s_sort,s_id',

			// In-property lookup
			'o_id,p_id',
			//'o_id,p_id,s_sort',

			// SMWSQLStore3Readers::getPropertySubjects
			// SELECT DISTINCT s_id FROM `smw_di_wikipage` WHERE (p_id='64' AND o_id='104') ORDER BY s_sort ASC
			//'o_id,p_id,s_id,s_sort'
		];
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

		return [ 'o_id' => $oid ];
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

		return [ 'o_id' => $oid ];
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

		if ( !is_array( $dbkeys ) || count( $dbkeys ) != 5 ) {
			throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
		}

		$namespace = intval( $dbkeys[1] );

		// Correctly interpret internal property keys
		if ( $namespace == SMW_NS_PROPERTY && $dbkeys[0] != '' &&
			$dbkeys[0]{0} == '_' && $dbkeys[2] == '' ) {

			try {
				$property = new DIProperty( $dbkeys[0] );
			} catch( PredefinedPropertyLabelMismatchException $e ) {
				// Most likely an outdated, no longer existing predefined
				// property, mark it as outdate
				$dbkeys[2] = SMW_SQL3_SMWIW_OUTDATED;

				return $this->newDiWikiPage( $dbkeys );
			}

			$wikipage = $property->getCanonicalDiWikiPage( $dbkeys[4] );

			if ( !is_null( $wikipage ) ) {
				return $wikipage;
			}
		}

		return $this->newDiWikiPage( $dbkeys );
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
