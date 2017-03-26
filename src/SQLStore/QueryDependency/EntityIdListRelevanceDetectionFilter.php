<?php

namespace SMW\SQLStore\QueryDependency;

use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use SMW\Utils\Timer;

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
class EntityIdListRelevanceDetectionFilter implements LoggerAwareInterface {

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
	private $propertyExemptionList = array();

	/**
	 * @var array
	 */
	private $affiliatePropertyDetectionList = array();

	/**
	 * @var LoggerInterface
	 */
	private $logger;

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
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
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

		$this->log( __METHOD__ . ' procTime (sec): ' . Timer::getElapsedTime( __CLASS__, 6 ) );

		return $filteredIdList;
	}

	private function applyFilterToTableChangeOp( $tableChangeOp, &$affiliateEntityList, &$combinedChangedEntityList ) {

		foreach ( $tableChangeOp->getFieldChangeOps( 'insert' ) as $insertFieldChangeOp ) {

			// Copy fields temporarily
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$insertFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
				$insertFieldChangeOp->set( 'key', $tableChangeOp->getFixedPropertyValueBy( 'key' ) );
			}

			$this->modifyEntityList( $insertFieldChangeOp, $affiliateEntityList, $combinedChangedEntityList );
		}

		foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $deleteFieldChangeOp ) {

			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$deleteFieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
				$deleteFieldChangeOp->set( 'key', $tableChangeOp->getFixedPropertyValueBy( 'key' ) );
			}

			$this->modifyEntityList( $deleteFieldChangeOp, $affiliateEntityList, $combinedChangedEntityList );
		}
	}

	private function modifyEntityList( $fieldChangeOp, &$affiliateEntityList, &$combinedChangedEntityList ) {
		$key = '';

		if ( $fieldChangeOp->has( 'key' ) ) {
			$key = $fieldChangeOp->get( 'key' );
		} elseif ( $fieldChangeOp->has( 'p_id' ) ) {
			$dataItem = $this->store->getObjectIds()->getDataItemById( $fieldChangeOp->get( 'p_id' ) );
			$key = $dataItem !== null ? $dataItem->getDBKey() : null;
		}

		// Exclusion before inclusion
		if ( isset( $this->propertyExemptionList[$key]) ) {
			$this->unsetEntityList( $fieldChangeOp, $combinedChangedEntityList );
			return;
		}

		if ( isset( $this->affiliatePropertyDetectionList[$key] ) && $fieldChangeOp->has( 's_id' ) ) {
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

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
