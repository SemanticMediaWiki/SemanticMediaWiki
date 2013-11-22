<?php

namespace SMW;

/**
 * Class that detects a change between a property and its store data
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class PropertyTypeComparator implements TitleAccess, DispatchableSubject {

	/** @var Store */
	protected $store;

	/** @var SemanticData */
	protected $semanticData;

	/** @var Settings */
	protected $settings;

	/** @var ObservableDispatcher */
	protected $dispatcher;

	/** @var boolean */
	protected $hasDisparity = false;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param SemanticData $semanticData
	 * @param Settings $settings
	 */
	public function __construct( Store $store, SemanticData $semanticData, Settings $settings ) {
		$this->store = $store;
		$this->semanticData = $semanticData;
		$this->settings = $settings;
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
	 * @see DispatchableSubject::registerDispatcher
	 *
	 * @since 1.9
	 *
	 * @param ObservableDispatcher $dispatcher
	 */
	public function registerDispatcher( ObservableDispatcher $dispatcher ) {
		$this->dispatcher = $dispatcher->setObservableSubject( $this );
		return $this;
	}

	/**
	 * Returns if a data disparity exists
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function hasDisparity() {
		return $this->hasDisparity;
	}

	/**
	 * Compare and compute the difference between invoked semantic data
	 * and the current store data
	 *
	 * @since 1.9
	 *
	 * @return PropertyTypeComparator
	 */
	public function runComparator() {
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
			foreach ( $this->settings->get( 'smwgDeclarationProperties' ) as $prop ) {
				$dataItem = new DIProperty( $prop );
				$oldValues = $this->store->getPropertyValues(
					$this->semanticData->getSubject(),
					$dataItem
				);

				$newValues = $this->semanticData->getPropertyValues( $dataItem );
				$update = $update || !$this->isEqual( $oldValues, $newValues );
			}
		}

		$this->notifyDispatcher( $update );

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

		$this->notifyDispatcher( !$this->isEqual( $oldfactors, $newfactors ) );

		Profiler::Out( __METHOD__, true );
	}

	/**
	 * Adds a Dispatcher job to resolve a disparity asynchronously
	 *
	 * @since 1.9
	 *
	 * @param boolean $addJob
	 */
	protected function notifyDispatcher( $addJob = true ) {
		if ( $addJob && !$this->hasDisparity ) {
			$this->dispatcher->setState( 'runUpdateDispatcher' );
			$this->hasDisparity = true;
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
