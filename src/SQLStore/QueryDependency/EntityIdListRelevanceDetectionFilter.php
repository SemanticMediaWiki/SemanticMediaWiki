<?php

namespace SMW\SQLStore\QueryDependency;

use Psr\Log\LoggerAwareTrait;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\Store;
use SMW\Utils\Timer;

/**
 * This class filters entities recorded in the ChangeOp
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

	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var ChangeOp
	 */
	private $changeOp = null;

	/**
	 * @var array
	 */
	private $propertyExemptionList = [];

	/**
	 * @var array
	 */
	private $affiliatePropertyDetectionList = [];

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param ChangeOp $changeOp
	 */
	public function __construct( Store $store, ChangeOp $changeOp ) {
		$this->store = $store;
		$this->changeOp = $changeOp;
	}

	/**
	 * @since 3.0
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->changeOp->getSubject();
	}

	/**
	 * @since 2.4
	 *
	 * @param array $propertyExemptionList
	 */
	public function setPropertyExemptionList( array $propertyExemptionList ) {
		$this->propertyExemptionList = array_flip(
			str_replace( ' ', '_', $propertyExemptionList )
		);
	}

	/**
	 * @since 2.4
	 *
	 * @param array $affiliatePropertyDetectionList
	 */
	public function setAffiliatePropertyDetectionList( array $affiliatePropertyDetectionList ) {
		$this->affiliatePropertyDetectionList = array_flip(
			str_replace( ' ', '_', $affiliatePropertyDetectionList )
		);
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getFilteredIdList() {

		Timer::start( __CLASS__ );

		$changedEntityIdSummaryList = array_flip(
			$this->changeOp->getChangedEntityIdSummaryList()
		);

		$affiliateEntityList = [];
		$tableChangeOps = $this->changeOp->getTableChangeOps();

		foreach ( $tableChangeOps as $tableChangeOp ) {
			$this->applyFilterToTableChangeOp(
				$tableChangeOp,
				$affiliateEntityList,
				$changedEntityIdSummaryList
			);
		}

		$filteredIdList = array_merge(
			array_keys( $changedEntityIdSummaryList ),
			array_keys( $affiliateEntityList )
		);

		$this->logger->info(
			[
				'QueryDependency',
				'EntityIdListRelevanceDetectionFilter',
				'Filter changeOp list',
				'procTime in sec: {procTime}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'procTime' => Timer::getElapsedTime( __CLASS__, 6 )
			]
		);

		return $filteredIdList;
	}

	private function applyFilterToTableChangeOp( $tableChangeOp, &$affiliateEntityList, &$changedEntityIdSummaryList ) {

		foreach ( $tableChangeOp->getFieldChangeOps( 'insert' ) as $insertFieldChangeOp ) {

			// Copy fields temporarily
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$insertFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
				$insertFieldChangeOp->set( 'key', $tableChangeOp->getFixedPropertyValueBy( 'key' ) );
			}

			$this->modifyEntityList( $insertFieldChangeOp, $affiliateEntityList, $changedEntityIdSummaryList );
		}

		foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $deleteFieldChangeOp ) {

			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$deleteFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
				$deleteFieldChangeOp->set( 'key', $tableChangeOp->getFixedPropertyValueBy( 'key' ) );
			}

			$this->modifyEntityList( $deleteFieldChangeOp, $affiliateEntityList, $changedEntityIdSummaryList );
		}
	}

	private function modifyEntityList( $fieldChangeOp, &$affiliateEntityList, &$changedEntityIdSummaryList ) {
		$key = '';

		if ( $fieldChangeOp->has( 'key' ) ) {
			$key = $fieldChangeOp->get( 'key' );
		} elseif ( $fieldChangeOp->has( 'p_id' ) ) {
			$dataItem = $this->store->getObjectIds()->getDataItemById( $fieldChangeOp->get( 'p_id' ) );
			$key = $dataItem !== null ? $dataItem->getDBKey() : null;
		}

		// Exclusion before inclusion
		if ( isset( $this->propertyExemptionList[$key]) ) {
			$this->unsetEntityList( $fieldChangeOp, $changedEntityIdSummaryList );
			return;
		}

		if ( isset( $this->affiliatePropertyDetectionList[$key] ) && $fieldChangeOp->has( 's_id' ) ) {
			$affiliateEntityList[$fieldChangeOp->get( 's_id' )] = true;
		}
	}

	private function unsetEntityList( $fieldChangeOp, &$changedEntityIdSummaryList ) {
		// Remove matched blacklisted property reference
		if ( $fieldChangeOp->has( 'p_id' ) ) {
			unset( $changedEntityIdSummaryList[$fieldChangeOp->get( 'p_id' )] );
		}

		// Remove associated subject ID's
		if ( $fieldChangeOp->has( 's_id' ) ) {
			unset( $changedEntityIdSummaryList[$fieldChangeOp->get( 's_id' )] );
		}
	}

}
