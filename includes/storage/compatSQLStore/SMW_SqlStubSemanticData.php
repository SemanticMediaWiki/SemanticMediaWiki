<?php
/**
 * The class in this file provides a subclass of SMWSemanticData that can store
 * prefetched values from SMW's SQL stores, and unstub this data on demand when
 * it is accessed.
 *
 * @file
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
  */

/**
 * This class provides a subclass of SMWSemanticData that can store
 * prefetched values from SMW's SQL stores, and unstub this data on demand when
 * it is accessed.
 *
 * @since 1.6
 *
 * @ingroup SMWStore
 */
class SMWSqlStubSemanticData extends SMWSemanticData {

	/**
	 * Stub property data that is not part of $mPropVals and $mProperties
	 * yet. Entries use property keys as keys. The value is an array of
	 * DBkey-arrays that define individual datavalues. The stubs will be
	 * set up when first accessed.
	 *
	 * @var array
	 */
	protected $mStubPropVals = array();

	/**
	 * SMWDIWikiPage object that is the subject of this container.
	 * Subjects that are null are used to represent "internal objects"
	 * only.
	 *
	 * @var SMWDIWikiPage
	 */
	protected $mSubject;

	/**
	 * Create a new SMWSqlStubSemanticData object that holds the data of a
	 * given SMWSemanticData object. Array assignments create copies in PHP
	 * so the arrays are distinct in input and output object. The object
	 * references are copied as references in a shallow way. This is
	 * sufficient as the data items used there are immutable.
	 *
	 * @param $semanticData SMWSemanticData
	 * @return SMWSqlStubSemanticData
	 */
	public static function newFromSemanticData( SMWSemanticData $semanticData ) {
		$result = new SMWSqlStubSemanticData( $semanticData->getSubject() );
		$result->mPropVals = $semanticData->mPropVals;
		$result->mProperties = $semanticData->mProperties;
		$result->mHasVisibleProps = $semanticData->mHasVisibleProps;
		$result->mHasVisibleSpecs = $semanticData->mHasVisibleSpecs;
		$result->stubObject = $semanticData->stubObject;
		return $result;
	}

	/**
	 * Get the array of all properties that have stored values.
	 *
	 * @return array of SMWDIProperty objects
	 */
	public function getProperties() {
		$this->unstubProperties();
		return parent::getProperties();
	}

	/**
	 * Get the array of all stored values for some property.
	 *
	 * @param $property SMWDIProperty
	 * @return array of SMWDataItem
	 */
	public function getPropertyValues( SMWDIProperty $property ) {
		if ( $property->isInverse() ) { // we never have any data for inverses
			return array();
		}

		if ( array_key_exists( $property->getKey(), $this->mStubPropVals ) ) {
			$this->unstubProperty( $property->getKey(), $property );
			$propertyTypeId = $property->findPropertyTypeID();
			$propertyDiId = SMWDataValueFactory::getDataItemId( $propertyTypeId );

			foreach ( $this->mStubPropVals[$property->getKey()] as $dbkeys ) {
				try {
					if ( $propertyDiId == SMWDataItem::TYPE_CONTAINER ) {
						$diSubWikiPage = SMWCompatibilityHelpers::dataItemFromDBKeys( '_wpg', $dbkeys );
						$semanticData = new SMWContainerSemanticData( $diSubWikiPage );
						$semanticData->copyDataFrom( smwfGetStore()->getSemanticData( $diSubWikiPage ) );

						$di = new SMWDIContainer( $semanticData );
					} else {
						$di = SMWCompatibilityHelpers::dataItemFromDBKeys( $propertyTypeId, $dbkeys );
					}

					if ( $this->mNoDuplicates ) {
						$this->mPropVals[$property->getKey()][$di->getHash()] = $di;
					} else {
						$this->mPropVals[$property->getKey()][] = $di;
					}
				} catch ( SMWDataItemException $e ) {
					// ignore data
				}
			}

			unset( $this->mStubPropVals[$property->getKey()] );
		}

		return parent::getPropertyValues( $property );
	}

	/**
	 * Return true if there are any visible properties.
	 *
	 * @return boolean
	 */
	public function hasVisibleProperties() {
		$this->unstubProperties();
		return parent::hasVisibleProperties();
	}

	/**
	 * Return true if there are any special properties that can
	 * be displayed.
	 *
	 * @return boolean
	 */
	public function hasVisibleSpecialProperties() {
		$this->unstubProperties();
		return parent::hasVisibleSpecialProperties();
	}

	/**
	 * Add data in abbreviated form so that it is only expanded if needed. The property key
	 * is the DB key (string) of a property value, whereas valuekeys is an array of DBkeys for
	 * the added value that will be used to initialize the value if needed at some point.
	 *
	 * @param $propertyKey string
	 * @param $valueKeys array
	 */
	public function addPropertyStubValue( $propertyKey, array $valueKeys ) {
		$this->mStubPropVals[$propertyKey][] = $valueKeys;
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear() {
		$this->mStubPropVals = array();
		parent::clear();
	}

	/**
	 * Process all mProperties that have been added as stubs.
	 * Associated data may remain in stub form.
	 */
	protected function unstubProperties() {
		foreach ( $this->mStubPropVals as $pkey => $values ) { // unstub property values only, the value lists are still kept as stubs
			try {
				$this->unstubProperty( $pkey );
			} catch ( SMWDataItemException $e ) {
				// Likely cause: a property name from the DB is no longer valid.
				// Do nothing; we could unset the data, but it will never be
				// unstubbed anyway if there is no valid property DI for it.
			}
		}
	}

	/**
	 * Unstub a single property from the stub data array. If available, an
	 * existing object for that property might be provided, so we do not
	 * need to make a new one. It is not checked if the object matches the
	 * property name.
	 *
	 * @param string $propertyKey
	 * @param SMWDIProperty $diProperty if available
	 * @throws SMWDataItemException if property key is not valid
	 * 	and $diProperty is null
	 */
	protected function unstubProperty( $propertyKey, $diProperty = null ) {
		if ( !array_key_exists( $propertyKey, $this->mProperties ) ) {
			if ( is_null( $diProperty ) ) {
				//$propertyDV = SMWPropertyValue::makeProperty( $propertyKey );
				//$diProperty = $propertyDV->getDataItem();
				$diProperty = new SMWDIProperty( $propertyKey, false );
			}

			$this->mProperties[$propertyKey] = $diProperty;

			if ( !$diProperty->isUserDefined() ) {
				if ( $diProperty->isShown() ) {
					$this->mHasVisibleSpecs = true;
					$this->mHasVisibleProps = true;
				}
			} else {
				$this->mHasVisibleProps = true;
			}
		}
	}

}