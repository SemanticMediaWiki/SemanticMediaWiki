<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * This class filters entities recorded in the CompositePropertyTableDiffIterator
 * and applies a relevance rule set by:
 *
 * - Remove exempted properties (not relevant)
 * - Add properties that are affiliated on a relational change
 *
 * By affiliation implies that a property listed is not directly related to a query
 * dependency, yet it is monitored and can, if altered trigger a dependency update
 * that normally is only reserved to dependent properties.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class EntityIdListRelevanceDetectionFilter {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var CompositePropertyTableDiffIterator
	 */
	private $compositePropertyTableDiffIterator = null;

	/**
	 * @var array
	 */
	private $propertyExemptionlist = array();

	/**
	 * @var array
	 */
	private $affiliatePropertyDetectionlist = array();

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 */
	public function __construct( Store $store, CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {
		$this->store = $store;
		$this->compositePropertyTableDiffIterator = $compositePropertyTableDiffIterator;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $propertyExemptionlist
	 */
	public function setPropertyExemptionlist( array $propertyExemptionlist ) {
		$this->propertyExemptionlist = array_flip(
			str_replace( ' ', '_', $propertyExemptionlist )
		);
	}

	/**
	 * @since 2.4
	 *
	 * @param array $affiliatePropertyDetectionlist
	 */
	public function setAffiliatePropertyDetectionlist( array $affiliatePropertyDetectionlist ) {
		$this->affiliatePropertyDetectionlist = array_flip(
			str_replace( ' ', '_', $affiliatePropertyDetectionlist )
		);
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getFilteredIdList() {

		$start = microtime( true );

		$combinedChangedEntityList = array_flip(
			$this->compositePropertyTableDiffIterator->getCombinedIdListOfChangedEntities()
		);

		$affiliateEntityList = array();
		$tableChangeOps = $this->compositePropertyTableDiffIterator->getTableChangeOps();

		foreach ( $tableChangeOps as $tableChangeOp ) {
			$this->applyFilterToTableChangeOp(
				$tableChangeOp,
				$affiliateEntityList,
				$combinedChangedEntityList
			);
		}

		$filteredIdList = array_merge(
			array_keys( $combinedChangedEntityList ),
			array_keys( $affiliateEntityList )
		);

		wfDebugLog( 'smw', __METHOD__ . ' processing (sec): ' . round( ( microtime( true ) - $start ), 6 )  );

		return $filteredIdList;
	}

	private function applyFilterToTableChangeOp( $tableChangeOp, &$affiliateEntityList, &$combinedChangedEntityList ) {

		foreach ( $tableChangeOp->getFieldChangeOps( 'insert' ) as $insertFieldChangeOp ) {

			// Copy fields temporarily
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$insertFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueFor( 'p_id' ) );
				$insertFieldChangeOp->set( 'key', $tableChangeOp->getFixedPropertyValueFor( 'key' ) );
			}

			$this->modifyEntityList( $insertFieldChangeOp, $affiliateEntityList, $combinedChangedEntityList );
		}

		foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $deleteFieldChangeOp ) {

			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$deleteFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueFor( 'p_id' ) );
				$deleteFieldChangeOp->set( 'key', $tableChangeOp->getFixedPropertyValueFor( 'key' ) );
			}

			$this->modifyEntityList( $deleteFieldChangeOp, $affiliateEntityList, $combinedChangedEntityList );
		}
	}

	private function modifyEntityList( $fieldChangeOp, &$affiliateEntityList, &$combinedChangedEntityList ) {
		$key = '';

		if ( $fieldChangeOp->has( 'key' ) ) {
			$key = $fieldChangeOp->get( 'key' );
		} elseif ( $fieldChangeOp->has( 'p_id' ) ) {
			$dataItem = $this->store->getObjectIds()->getDataItemForId( $fieldChangeOp->get( 'p_id' ) );
			$key = $dataItem->getDBKey();
		}

		// Exclusion before inclusion
		if ( isset( $this->propertyExemptionlist[$key]) ) {
			$this->unsetEntityList( $fieldChangeOp, $combinedChangedEntityList );
			return;
		}

		if ( isset( $this->affiliatePropertyDetectionlist[$key] ) && $fieldChangeOp->has( 's_id' ) ) {
			$affiliateEntityList[$fieldChangeOp->get( 's_id' )] = true;
		}
	}

	private function unsetEntityList( $fieldChangeOp, &$combinedChangedEntityList ) {
		// Remove matched blacklisted property reference
		if ( $fieldChangeOp->has( 'p_id' ) ) {
			unset( $combinedChangedEntityList[$fieldChangeOp->get( 'p_id' )] );
		}

		// Remove associated subject ID's
		if ( $fieldChangeOp->has( 's_id' ) ) {
			unset( $combinedChangedEntityList[$fieldChangeOp->get( 's_id' )] );
		}
	}

}
