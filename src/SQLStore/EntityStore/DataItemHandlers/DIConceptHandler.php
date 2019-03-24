<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\DIConcept;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;

/**
 * This class implements Store access to Concept data items.
 *
 * @note The table layout and behavior of this class is not coherent with the
 * way that other DIs work. This is because of the unfortunate use of the
 * concept table to store extra cache data, but also due to the design of
 * concept DIs. This will be cleaned up at some point.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DIConceptHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return [
			'concept_txt'  => FieldType::TYPE_BLOB,
			'concept_docu' => FieldType::TYPE_BLOB,
			'concept_features' => FieldType::FIELD_NAMESPACE,
			'concept_size'  => FieldType::FIELD_NAMESPACE,
			'concept_depth' => FieldType::FIELD_NAMESPACE,
			'cache_date'    => FieldType::TYPE_INT_UNSIGNED,
			'cache_count'   => FieldType::TYPE_INT_UNSIGNED
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return [
			'concept_txt'  => FieldType::TYPE_BLOB,
			'concept_docu' => FieldType::TYPE_BLOB,
			'concept_features' => FieldType::FIELD_NAMESPACE,
			'concept_size'  => FieldType::FIELD_NAMESPACE,
			'concept_depth' => FieldType::FIELD_NAMESPACE,
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return [
			'concept_txt' => $dataItem->getConceptQuery(),
			'concept_docu' => $dataItem->getDocumentation(),
			'concept_features' => $dataItem->getQueryFeatures(),
			'concept_size' => $dataItem->getSize(),
			'concept_depth' => $dataItem->getDepth()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		return [
			'concept_txt' => $dataItem->getConceptQuery(),
			'concept_docu' => $dataItem->getDocumentation(),
			'concept_features' => $dataItem->getQueryFeatures(),
			'concept_size' => $dataItem->getSize(),
			'concept_depth' => $dataItem->getDepth()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField() {
		return 'concept_txt';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField() {
		return 'concept_txt';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ) {

		if ( is_array( $dbkeys) && count( $dbkeys ) == 5 ) {
			return new DIConcept(
				$dbkeys[0],
				smwfXMLContentEncode( $dbkeys[1] ),
				$dbkeys[2],
				$dbkeys[3],
				$dbkeys[4]
			);
		}

		throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
	}

}
