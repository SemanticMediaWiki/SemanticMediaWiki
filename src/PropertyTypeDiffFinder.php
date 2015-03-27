<?php

namespace SMW;

use SMWDataItem;

/**
 * Class that detects a change between a property and its store data
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class PropertyTypeDiffFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var boolean
	 */
	private $hasDiff = false;

	/**
	 * @var array
	 */
	private $propertiesToCompare =  array();

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param SemanticData $semanticData
	 */
	public function __construct( Store $store, SemanticData $semanticData ) {
		$this->store = $store;
		$this->semanticData = $semanticData;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $declarationProperties
	 */
	public function setPropertiesToCompare( array $propertiesToCompare ) {
		$this->propertiesToCompare = $propertiesToCompare;
	}

	/**
	 * Returns if a data disparity exists
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function hasDiff() {
		return $this->hasDiff;
	}

	/**
	 * Compare and compute the difference between invoked semantic data
	 * and the current store data
	 *
	 * @since 1.9
	 *
	 * @return $this
	 */
	public function findDiff() {

		if ( $this->semanticData->getSubject()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->comparePropertyTypes();
			$this->compareConversionTypedFactors();
		}

		return $this;
	}

	/**
	 * Compare and find changes related to the property type
	 *
	 * @since 1.9
	 */
	private function comparePropertyTypes() {

		$update = false;
		$propertyType  = new DIProperty( DIProperty::TYPE_HAS_TYPE );

		// Get values from the store
		$oldType = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			$propertyType
		);

		// Get values currently hold by the semantic container
		$newType = $this->semanticData->getPropertyValues( $propertyType );

		// Compare old and new type
		if ( !$this->isEqual( $oldType, $newType ) ) {
			$update = true;
		} else {
			foreach ( $this->propertiesToCompare as $property ) {
				$update = $update || !$this->isEqualForProperty( new DIProperty( $property ) );
			}
		}

		$this->notifyDispatcher( $update );
	}

	/**
	 * Compare and find changes related to conversion factor
	 */
	private function compareConversionTypedFactors() {

		$pconversion  = new DIProperty( DIProperty::TYPE_CONVERSION );

		$newfactors = $this->semanticData->getPropertyValues( $pconversion );
		$oldfactors = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			$pconversion
		);

		$this->notifyDispatcher( !$this->isEqual( $oldfactors, $newfactors ) );
	}

	private function notifyDispatcher( $addJob = true ) {
		if ( $addJob && !$this->hasDiff ) {

			$eventHandler = ApplicationFactory::getInstance()->getEventHandler();

			$dispatchContext = $eventHandler->newDispatchContext();
			$dispatchContext->set( 'subject', $this->semanticData->getSubject() );

			$eventHandler->getEventDispatcher()->dispatch(
				'property.type.change',
				$dispatchContext
			);

			$this->hasDiff = true;
		}
	}

	private function isEqualForProperty( DIProperty $property ) {

		$currentStoreValues = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			$property
		);

		return $this->isEqual(
			$currentStoreValues,
			$this->semanticData->getPropertyValues( $property )
		);
	}

	/**
	 * Helper function that compares two arrays of data values to check whether
	 * they contain the same content. Returns true if the two arrays contain the
	 * same data values (irrespective of their order), false otherwise.
	 *
	 * @param SMWDataItem[] $oldDataValue
	 * @param SMWDataItem[] $newDataValue
	 *
	 * @return boolean
	 */
	private function isEqual( array $oldDataValue, array $newDataValue ) {

		// The hashes of all values of both arrays are taken, then sorted
		// and finally concatenated, thus creating one long hash out of each
		// of the data value arrays. These are compared.
		$values = array();
		foreach ( $oldDataValue as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$oldDataValueHash = implode( '___', $values );

		$values = array();
		foreach ( $newDataValue as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$newDataValueHash = implode( '___', $values );

		return $oldDataValueHash == $newDataValueHash;
	}

}
