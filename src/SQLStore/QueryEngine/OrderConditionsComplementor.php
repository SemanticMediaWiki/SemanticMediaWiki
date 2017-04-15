<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\DataValueFactory;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\DataValues\PropertyChainValue;
use RuntimeException;

/**
 * Modifies a given query object at $qid to account for all ordering conditions
 * in the Query $query. It is always required that $qid is the id of a query
 * that joins with the SMW ID_TABELE so that the field alias.smw_title is
 * available for default sorting.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class OrderConditionsComplementor {

	/**
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder;

	/**
	 * @var DescriptionFactory
	 */
	private $descriptionFactory;

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 *
	 * @var string[]
	 */
	private $sortKeys = array();

	/**
	 * @var boolean
	 */
	private $isSupported = true;

	/**
	 * @since 2.5
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 */
	public function __construct( QuerySegmentListBuilder $querySegmentListBuilder ) {
		$this->querySegmentListBuilder = $querySegmentListBuilder;
		$this->descriptionFactory = new DescriptionFactory();
	}

	/**
	 * @since 2.5
	 *
	 * @param array $sortKeys
	 */
	public function setSortKeys( $sortKeys ) {
		$this->sortKeys = $sortKeys;
	}

	/**
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function getSortKeys() {
		return $this->sortKeys;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->querySegmentListBuilder->getErrors();
	}

	/**
	 * @since 2.5
	 *
	 * @param boolean $isSupported
	 */
	public function isSupported( $isSupported ) {
		$this->isSupported = $isSupported;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $qid
	 *
	 * @return QuerySegment[]
	 */
	public function applyOrderConditions( $qid ) {

		if ( !$this->isSupported ) {
			return $this->querySegmentListBuilder->getQuerySegmentList();
		}

		$querySegment = $this->querySegmentListBuilder->findQuerySegment(
			$qid
		);

		$extraDescriptions = $this->collectExtraDescriptionsFromSortKeys(
			$querySegment
		);

		if ( $extraDescriptions !== array() ) {
			$this->addConjunctionFromExtraDescriptions( $querySegment, $extraDescriptions );
		}

		return $this->querySegmentListBuilder->getQuerySegmentList();
	}

	private function collectExtraDescriptionsFromSortKeys( $querySegment ) {

		$extraDescriptions = array();

		foreach ( $this->sortKeys as $label => $order ) {

			if ( !is_string( $label ) ) {
				throw new RuntimeException( "Expected a string value as sortkey" );
			}

			if ( ( $description = $this->findExtraDescriptionBy( $querySegment, $label, $order ) ) instanceof Description ) {
				$extraDescriptions[] = $description;
			}
		}

		return $extraDescriptions;
	}

	private function findExtraDescriptionBy( $querySegment, $label, $order ) {

		$description = null;

		// Is assigned, leave ...
		if ( array_key_exists( $label, $querySegment->sortfields ) ) {
			return $description;
		}

		// Find missing property to sort by.
		if ( $label === '' ) { // Sort by first result column (page titles).
			$querySegment->sortfields[$label] = "$querySegment->alias.smw_sortkey";
		} elseif ( $label === '#' ) { // Sort by first result column (page titles).
			// PHP7 showed a rather erratic behaviour where in cases
			// the sortkey contains the same string for comparison, the
			// result returned from the DB was mixed in order therefore
			// using # as indicator to search for additional fields if
			// no specific property is given (see test cases in #1534)
			$querySegment->sortfields[$label] = "$querySegment->alias.smw_sortkey,$querySegment->alias.smw_title,$querySegment->alias.smw_subobject";
		} elseif ( PropertyChainValue::isChained( $label ) ) { // Try to extend query.
			$propertyChainValue = DataValueFactory::getInstance()->newDataValueByType( PropertyChainValue::TYPE_ID );
			$propertyChainValue->setUserValue( $label );

			if ( !$propertyChainValue->isValid() ) {
				return $description;
			}

			$lastDataItem = $propertyChainValue->getLastPropertyChainValue()->getDataItem();

			$description = $this->descriptionFactory->newSomeProperty(
				$lastDataItem,
				$this->descriptionFactory->newThingDescription()
			);

			// #2176, Set a different membership in case duplicate detection is
			// enabled, the fingerprint will be distinguishable from a condition
			// with another ThingDescription for the same property that would
			// otherwise create a "Error: 1066 Not unique table/alias: 't3'"
			$description->setMembership( $label );

			foreach ( $propertyChainValue->getPropertyChainValues() as $val ) {
				$description = $this->descriptionFactory->newSomeProperty(
					$val->getDataItem(),
					$description
				);
			}

			// Add and replace Foo.Bar=asc with Bar=asc as we ultimately only
			// order to the result of the last element
			$this->sortKeys[$lastDataItem->getKey()] = $order;
			unset( $this->sortKeys[$label] );
		} else { // Try to extend query.
			$sortprop = DataValueFactory::getInstance()->newPropertyValueByLabel( $label );

			if ( $sortprop->isValid() ) {
				$description = $this->descriptionFactory->newSomeProperty(
					$sortprop->getDataItem(),
					$this->descriptionFactory->newThingDescription()
				);
			}
		}

		return $description;
	}

	private function addConjunctionFromExtraDescriptions( $querySegment, array $extraDescriptions ) {

		$this->querySegmentListBuilder->setSortKeys(
			$this->sortKeys
		);

		$this->querySegmentListBuilder->getQuerySegmentFrom(
			$this->descriptionFactory->newConjunction( $extraDescriptions )
		);

		// This is always an QuerySegment::Q_CONJUNCTION ...
		$newQuerySegment = $this->querySegmentListBuilder->findQuerySegment(
			$this->querySegmentListBuilder->getLastQuerySegmentId()
		);

		 // ... so just re-wire its dependencies
		foreach ( $newQuerySegment->components as $cid => $field ) {
			$querySegment->components[$cid] = $querySegment->joinfield;
			$querySegment->sortfields = array_merge(
				$querySegment->sortfields,
				$this->querySegmentListBuilder->findQuerySegment( $cid )->sortfields
			);
		}

		$this->querySegmentListBuilder->addQuerySegment( $querySegment );
	}

}
