<?php

namespace SMW\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exceptions\DataItemHandlerException;
use SMW\DIConcept;

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
	 * @since 1.8
	 *
	 * {@inheritDoc}
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
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {
		return array(
			'concept_txt' => $dataItem->getConceptQuery(),
			'concept_docu' => $dataItem->getDocumentation(),
			'concept_features' => $dataItem->getQueryFeatures(),
			'concept_size' => $dataItem->getSize(),
			'concept_depth' => $dataItem->getDepth()
		);
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {
		return array(
			'concept_txt' => $dataItem->getConceptQuery(),
			'concept_docu' => $dataItem->getDocumentation(),
			'concept_features' => $dataItem->getQueryFeatures(),
			'concept_size' => $dataItem->getSize(),
			'concept_depth' => $dataItem->getDepth()
		);
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
