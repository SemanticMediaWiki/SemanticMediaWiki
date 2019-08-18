<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DataTypeRegistry;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exception\DataItemException;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\SQLStore;
use SMW\StoreFactory;
use SMWDataItem as DataItem;
use SMWSemanticData as SemanticData;

/**
 * This class provides a subclass of SemanticData that can store prefetched values
 * from the SQL store, and unstub this data on demand when it is accessed.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Markus Krötzs
 * @author mwjames
 */
class StubSemanticData extends SemanticData {

	/**
	 * @var SQLStore
	 */
	protected $store;

	/**
	 * Stub property data that is not part of $mPropVals and $mProperties
	 * yet. Entries use property keys as keys. The value is an array of
	 * DBkey-arrays that define individual datavalues. The stubs will be
	 * set up when first accessed.
	 *
	 * @since 1.8
	 *
	 * @var array
	 */
	protected $mStubPropVals = [];

	/**
	 * DIWikiPage object that is the subject of this container.
	 * Subjects that are null are used to represent "internal objects"
	 * only.
	 *
	 * @since 1.8
	 *
	 * @var DIWikiPage
	 */
	protected $mSubject;

	/**
	 * Whether SubSemanticData have been requested and added
	 *
	 * @var boolean
	 */
	private $subSemanticDataInit = false;

	/**
	 * @since 1.8
	 *
	 * @param DIWikiPage $subject to which this data refers
	 * @param SQLStore $store (the parent store)
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public function __construct( DIWikiPage $subject, SQLStore $store, $noDuplicates = true ) {
		$this->store = $store;
		parent::__construct( $subject, $noDuplicates );
	}

	/**
	 * Required to support php-serialization
	 *
	 * @since 2.3
	 *
	 * @return array
	 */
	public function __sleep() {
		return [ 'mSubject', 'mPropVals', 'mProperties', 'subSemanticData', 'mStubPropVals', 'options', 'extensionData' ];
	}

	/**
	 * @since 2.3
	 */
	public function __wakeup() {
		$this->store = StoreFactory::getStore( 'SMW\SQLStore\SQLStore' );
	}

	/**
	 * Create a new StubSemanticData object that holds the data of a
	 * given SemanticData object. Array assignments create copies in PHP
	 * so the arrays are distinct in input and output object. The object
	 * references are copied as references in a shallow way. This is
	 * sufficient as the data items used there are immutable.
	 *
	 * @since 1.8
	 *
	 * @param $semanticData SemanticData
	 * @param SQLStore $store
	 *
	 * @return StubSemanticData
	 */
	public static function newFromSemanticData( SemanticData $semanticData, SQLStore $store ) {
		$result = new self( $semanticData->getSubject(), $store );
		$result->mPropVals = $semanticData->mPropVals;
		$result->mProperties = $semanticData->mProperties;
		$result->mHasVisibleProps = $semanticData->mHasVisibleProps;
		$result->mHasVisibleSpecs = $semanticData->mHasVisibleSpecs;
		$result->stubObject = $semanticData->stubObject;
		$result->sequenceMap = $semanticData->sequenceMap;
		return $result;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $sid
	 * @param [] $sequenceMap
	 */
	public function setSequenceMap( $sid, $sequenceMap ) {
		$this->sequenceMap = is_array( $sequenceMap ) ? $sequenceMap : [];
	}

	/**
	 * Get the array of all properties that have stored values.
	 *
	 * @since 1.8
	 *
	 * @return array of SMWDIProperty objects
	 */
	public function getProperties() {
		$this->unstubProperties();
		return parent::getProperties();
	}

	/**
	 * @see SemanticData::hasProperty
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function hasProperty( DIProperty $property ) {
		$this->unstubProperties();
		return parent::hasProperty( $property );
	}

	/**
	 * Get the array of all stored values for some property.
	 *
	 * @since 1.8
	 *
	 * @param DIProperty $property
	 *
	 * @return array of DataItem
	 */
	public function getPropertyValues( DIProperty $property ) {

		// we never have any data for inverses
		if ( $property->isInverse() ) {
			return [];
		}

		if ( array_key_exists( $property->getKey(), $this->mStubPropVals ) ) {
			$this->unstubPropertyValues( $property );
		}

		return parent::getPropertyValues( $property );
	}

	/**
	 * @see SemanticData::getSubSemanticData
	 *
	 * @note SubSemanticData are added only on request to avoid unnecessary DB
	 * transactions
	 *
	 * @since 2.0
	 */
	public function getSubSemanticData() {

		if ( $this->subSemanticDataInit ) {
			return parent::getSubSemanticData();
		}

		$this->subSemanticDataInit = true;

		foreach ( $this->getProperties() as $property ) {

			// #619 Do not resolve subobjects for redirects
			if ( !DataTypeRegistry::getInstance()->isSubDataType( $property->findPropertyTypeID() ) ) {
				continue;
			}

			if ( $this->isRedirect() ) {
				continue;
			}

			$this->initSubSemanticData( $property );
		}

		return parent::getSubSemanticData();
	}

	/**
	 * @see SemanticData::hasSubSemanticData
	 *
	 * @note This method will initialize SubSemanticData first if it wasn't done
	 * yet to ensure data consistency
	 *
	 * @since 2.0
	 */
	public function hasSubSemanticData( $subobjectName = null ) {

		if ( !$this->subSemanticDataInit ) {
			$this->getSubSemanticData();
		}

		return parent::hasSubSemanticData( $subobjectName );
	}

	/**
	 * @see SemanticData::findSubSemanticData
	 *
	 * @since 2.5
	 */
	public function findSubSemanticData( $subobjectName ) {

		if ( !$this->subSemanticDataInit ) {
			$this->getSubSemanticData();
		}

		return parent::findSubSemanticData( $subobjectName );
	}

	/**
	 * Remove a value for a property identified by its DataItem object.
	 * This method removes a property-value specified by the property and
	 * dataitem. If there are no more property-values for this property it
	 * also removes the property from the mProperties.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param $property SMWDIProperty
	 * @param $dataItem DataItem
	 *
	 * @since 1.8
	 */
	public function removePropertyObjectValue( DIProperty $property, DataItem $dataItem ) {
		$this->unstubProperties();
		$this->getPropertyValues( $property );
		parent::removePropertyObjectValue($property, $dataItem);
	}

	/**
	 * Return true if there are any visible properties.
	 *
	 * @since 1.8
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
	 * @since 1.8
	 *
	 * @return boolean
	 */
	public function hasVisibleSpecialProperties() {
		$this->unstubProperties();
		return parent::hasVisibleSpecialProperties();
	}

	/**
	 * Add data in abbreviated form so that it is only expanded if needed.
	 * The property key is the DB key (string) of a property value, whereas
	 * valuekeys is an array of DBkeys for the added value that will be
	 * used to initialize the value if needed at some point. If there is
	 * only one valuekey, a single string can be used.
	 *
	 * @since 1.8
	 * @param string $propertyKey
	 * @param array|string $valueKeys
	 */
	public function addPropertyStubValue( $propertyKey, $valueKeys ) {
		$this->mStubPropVals[$propertyKey][] = $valueKeys;
	}

	/**
	 * Delete all data other than the subject.
	 *
	 * @since 1.8
	 */
	public function clear() {
		$this->mStubPropVals = [];
		parent::clear();
	}

	/**
	 * Process all mProperties that have been added as stubs.
	 * Associated data may remain in stub form.
	 *
	 * @since 1.8
	 */
	protected function unstubProperties() {
		foreach ( $this->mStubPropVals as $pkey => $values ) { // unstub property values only, the value lists are still kept as stubs
			try {
				$this->unstubProperty( $pkey );
			} catch ( DataItemException $e ) {
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
	 * @since 1.8
	 *
	 * @param string $propertyKey
	 * @param SMWDIProperty $diProperty if available
	 *
	 * @throws DataItemException if property key is not valid
	 * 	and $diProperty is null
	 */
	protected function unstubProperty( $propertyKey, $diProperty = null ) {
		if ( !array_key_exists( $propertyKey, $this->mProperties ) ) {
			if ( is_null( $diProperty ) ) {
				$diProperty = new DIProperty( $propertyKey, false );
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

	protected function isRedirect() {
		return $this->store->getObjectIds()->isRedirect( $this->mSubject );
	}

	private function unstubPropertyValues( DIProperty $property ) {

		// Not catching exception here; the
		$this->unstubProperty( $property->getKey(), $property );
		$propertyTypeId = $property->findPropertyTypeID();

		$propertyDiId = DataTypeRegistry::getInstance()->getDataItemId( $propertyTypeId );
		$diHandler = $this->store->getDataItemHandlerForDIType( $propertyDiId );

		foreach ( $this->mStubPropVals[$property->getKey()] as $dbkeys ) {

			try {
				$dataItem = $diHandler->dataItemFromDBKeys( $dbkeys );
			} catch ( DataItemHandlerException $e ) {
				continue;
			}

			if ( $this->mNoDuplicates ) {
				$this->mPropVals[$property->getKey()][md5( $dataItem->getHash() )] = $dataItem;
			} else {
				$this->mPropVals[$property->getKey()][] = $dataItem;
			}
		}

		unset( $this->mStubPropVals[$property->getKey()] );
	}

	private function initSubSemanticData( DIProperty $property ) {
		foreach ( $this->getPropertyValues( $property ) as $value ) {

			if ( !$value instanceof DIWikiPage || $value->getSubobjectName() === '' ) {
				continue;
			}

			if ( $this->hasSubSemanticData( $value->getSubobjectName() ) ) {
				continue;
			}

			$this->addSubSemanticData( $this->store->getSemanticData( $value ) );
		}
	}

}
