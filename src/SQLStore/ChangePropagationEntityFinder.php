<?php

namespace SMW\SQLStore;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\IteratorFactory;
use SMW\DataTypeRegistry;
use SMWDataItem as DataItem;

/**
 * Find all subjects to a property related to a change propagation (only expected
 * to be used by `ChangePropagationDispatchJob`).
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationEntityFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var boolean
	 */
	private $isTypePropagation = false;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( Store $store, IteratorFactory $iteratorFactory ) {
		$this->store = $store;
		$this->iteratorFactory = $iteratorFactory;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isTypePropagation
	 */
	public function isTypePropagation( $isTypePropagation ) {
		$this->isTypePropagation = (bool)$isTypePropagation;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 *
	 * @return Iterator
	 */
	public function findByProperty( DIProperty $property ) {

		$dataItems = array();
		$appendIterator = $this->iteratorFactory->newAppendIterator();

		// Allow entities with NULL to be listed since we want them to be cleaned
		$requestOptions = new RequestOptions();
		$requestOptions->reqNullExpr = false;

		$res = $this->store->getAllPropertySubjects(
			$property,
			$requestOptions
		);

		$appendIterator->add(
			$res
		);

		// Select any remaining references that are hidden or have been left out
		// during an update
		$appendIterator->add(
			$this->fetchOtherReferencesOnTypePropagation( $property )
		);

		$dataItems = $this->store->getPropertySubjects(
			new DIProperty( DIProperty::TYPE_ERROR ),
			$property->getCanonicalDiWikiPage()
		);

		$appendIterator->add(
			$dataItems
		);

		return $appendIterator;
	}

	private function fetchOtherReferencesOnTypePropagation( $property ) {

		// Find other references only on a type propagation (which causes a
		// change of table/id assignments) for entity references
		if ( $this->isTypePropagation === false ) {
			return [];
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$pid = $this->store->getObjectIds()->getSMWPropertyID( $property );

		$dataItemTables =  $this->store->getPropertyTableInfoFetcher()->getDefaultDataItemTables();
		$idList = [];

		// Matches may temporary create duplicates in regrads to
		// Store::getAllPropertySubjects but it will be dealt with by the
		// deduplication in the ChangePropagationUpdateJob
		foreach ( $dataItemTables as $tableName ) {

			// Select any references that are hidden or remained active
			$rows = $connection->select(
				$connection->tableName( $tableName ),
				array(
					's_id'
				),
				array(
					'p_id' => $pid
				),
				__METHOD__
			);

			foreach ( $rows as $row ) {
				$idList[] = $row->s_id;
			}
		}

		if ( $idList === array() ) {
			return $idList;
		}

		return $this->store->getObjectIds()->getDataItemPoolHashListFor( $idList );
	}

}
