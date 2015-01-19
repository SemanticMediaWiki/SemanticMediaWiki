<?php

namespace SMW;

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
	protected $store;

	/**
	 * @var SemanticData
	 */
	protected $semanticData;

	/**
	 * @var boolean
	 */
	protected $hasDiff = false;

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
	 * Returns a Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->semanticData->getSubject()->getTitle();
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
	 * @return PropertyTypeComparator
	 */
	public function findDiff() {
		Profiler::In( __METHOD__, true );

		if ( $this->semanticData->getSubject()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->comparePropertyTypes();
			$this->compareConversionTypedFactors();
		}

		Profiler::Out( __METHOD__, true );
		return $this;
	}

	/**
	 * Compare and find changes related to the property type
	 *
	 * @since 1.9
	 */
	protected function comparePropertyTypes() {
		Profiler::In( __METHOD__, true );

		$update = false;
		$ptype  = new DIProperty( DIProperty::TYPE_HAS_TYPE );

		// Get values from the store
		$oldtype = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			$ptype
		);

		// Get values currently hold by the semantic container
		$newtype = $this->semanticData->getPropertyValues( $ptype );

		// Compare old and new type
		if ( !$this->isEqual( $oldtype, $newtype ) ) {
			$update = true;
		} else {

			// Compare values (in case of _PVAL (allowed values) for a
			// property change must be processed again)
			$smwgDeclarationProperties = ApplicationFactory::getInstance()->getSettings()->get( 'smwgDeclarationProperties' );

			foreach ( $smwgDeclarationProperties as $prop ) {
				$dataItem = new DIProperty( $prop );
				$oldValues = $this->store->getPropertyValues(
					$this->semanticData->getSubject(),
					$dataItem
				);

				$newValues = $this->semanticData->getPropertyValues( $dataItem );
				$update = $update || !$this->isEqual( $oldValues, $newValues );
			}
		}

		$this->notifyUpdateDispatcher( $update );

		Profiler::Out( __METHOD__, true );
	}

	/**
	 * Compare and find changes related to conversion factor
	 *
	 * @since 1.9
	 */
	protected function compareConversionTypedFactors() {
		Profiler::In( __METHOD__, true );

		$pconversion  = new DIProperty( DIProperty::TYPE_CONVERSION );

		$newfactors = $this->semanticData->getPropertyValues( $pconversion );
		$oldfactors = $this->store->getPropertyValues(
			$this->semanticData->getSubject(),
			$pconversion
		);

		$this->notifyUpdateDispatcher( !$this->isEqual( $oldfactors, $newfactors ) );

		Profiler::Out( __METHOD__, true );
	}

	/**
	 * @since 1.9
	 *
	 * @param boolean $addJob
	 */
	protected function notifyUpdateDispatcher( $addJob = true ) {
		if ( $addJob && !$this->hasDiff ) {

			ApplicationFactory::getInstance()
				->newJobFactory()
				->newUpdateDispatcherJob( $this->semanticData->getSubject()->getTitle() )
				->run();

			$this->hasDiff = true;
		}
	}

	/**
	 * Helper function that compares two arrays of data values to check whether
	 * they contain the same content. Returns true if the two arrays contain the
	 * same data values (irrespective of their order), false otherwise.
	 *
	 * @since 1.9
	 *
	 * @param $oldDataValue
	 * @param $newDataValue
	 */
	protected function isEqual( $oldDataValue, $newDataValue ) {

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

		return ( $oldDataValueHash == $newDataValueHash );
	}

}
