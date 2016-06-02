<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\Store;

/**
 * This class filters entities recorded in CompositePropertyTableDiffIterator
 * by applying a rule set.
 *
 * - Exempted properties are removed from the update list
 * - Properties that are affiliated are checked and the related entity is added
 *   to the update list that would eventually trigger a dependency update
 *
 * By affiliation implies that a property listed is no directly related to a query
 * dependency, yet it is monitored and can, if altered trigger a dependency update
 * that normally only reserved for dependent properties.
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

		foreach ( $this->compositePropertyTableDiffIterator->getOrderedDiffByTable() as $tableName => $diff ) {
			$this->applyFilterToDiffElement( $diff, $affiliateEntityList, $combinedChangedEntityList );
		}

		$listOfChangedEntitiesByRule = array_merge(
			array_keys( $combinedChangedEntityList ),
			array_keys( $affiliateEntityList )
		);

		wfDebugLog( 'smw', __METHOD__ . ' processing (sec): ' . round( ( microtime( true ) - $start ), 6 )  );

		return $listOfChangedEntitiesByRule;
	}

	private function applyFilterToDiffElement( $diff, &$affiliateEntityList, &$combinedChangedEntityList ) {

		// User-defined
		if ( !isset( $diff['property'] ) ) {
			if ( isset( $diff['insert'] ) ) {
				foreach ( $diff['insert'] as $insert ) {
					$this->addIdToAffiliateEntityList( $insert, $affiliateEntityList, $combinedChangedEntityList );
				}
			}

			if ( isset( $diff['delete'] ) ) {
				foreach ( $diff['delete'] as $delete ) {
					$this->addIdToAffiliateEntityList( $delete, $affiliateEntityList, $combinedChangedEntityList );
				}
			}

			return;
		}

		// Exemption goes for inclusion

		// Fixed
		if ( isset( $this->propertyExemptionlist[$diff['property']['key']] ) ) {
			$this->unsetIdFromEntityList(
				$diff['property']['p_id'],
				$diff,
				$combinedChangedEntityList
			);

			return;
		}

		if ( !isset( $this->affiliatePropertyDetectionlist[$diff['property']['key']]) ) {
			return;
		}

		if ( isset( $diff['insert'] ) ) {
			foreach ( $diff['insert'] as $insert ) {
				$affiliateEntityList[$insert['s_id']] = true;
			}
		}

		if ( isset( $diff['delete'] ) ) {
			foreach ( $diff['delete'] as $delete ) {
				$affiliateEntityList[$delete['s_id']] = true;
			}
		}
	}

	private function addIdToAffiliateEntityList( $diff, &$affiliateEntityList, &$combinedChangedEntityList ) {

		$key = '';

		if ( isset( $diff['p_id'] ) ) {
			$dataItem = $this->store->getObjectIds()->getDataItemForId( $diff['p_id'] );
			$key = $dataItem->getDBKey();

			if ( isset( $this->propertyExemptionlist[$key]) ) {
				$this->unsetIdFromEntityList(
					$diff['p_id'],
					$diff,
					$combinedChangedEntityList
				);

				return null;
			}
		}

		if ( isset( $this->affiliatePropertyDetectionlist[$key]) ) {
			$affiliateEntityList[$diff['s_id']] = true;
		}
	}

	private function unsetIdFromEntityList( $id, $diff, &$combinedChangedEntityList ) {

		// Remove matched blacklisted property reference
		unset( $combinedChangedEntityList[$id] );

		if ( isset( $diff['s_id'] ) ) {
			unset( $combinedChangedEntityList[$diff['s_id']] );
		}

		// Remove associated subject ID's
		if ( isset( $diff['insert'] ) ) {
			foreach ( $diff['insert'] as $insert ) {
				unset( $combinedChangedEntityList[$insert['s_id']] );
			}
		}

		if ( isset( $diff['delete'] ) ) {
			foreach ( $diff['delete'] as $delete ) {
				unset( $combinedChangedEntityList[$delete['s_id']] );
			}
		}
	}

}
