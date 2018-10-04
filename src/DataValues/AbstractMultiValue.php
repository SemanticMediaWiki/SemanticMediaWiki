<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMWDataValue as DataValue;
use SMWPropertyListValue as PropertyListValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
abstract class AbstractMultiValue extends DataValue {

	/**
	 * @since 2.5
	 *
	 * @param string $userValue
	 *
	 * @return array
	 */
	abstract public function getValuesFromString( $userValue );

	/**
	 * @since 2.5
	 *
	 * @param DIProperty[] $properties
	 *
	 * @return DIProperty[]|null
	 */
	abstract public function setFieldProperties( array $properties );

	/**
	 * @since 2.5
	 *
	 * @return DIProperty[]|null
	 */
	abstract public function getProperties();

	/**
	 * Return the array (list) of properties that the individual entries of
	 * this datatype consist of.
	 *
	 * @since 2.5
	 *
	 * @return DIProperty[]|null
	 */
	abstract public function getPropertyDataItems();

	/**
	 * Create a list (array with numeric keys) containing the datavalue
	 * objects that this SMWRecordValue object holds. Values that are not
	 * present are set to null. Note that the first index in the array is
	 * 0, not 1.
	 *
	 * @since 2.5
	 *
	 * @return DataItem[]|null
	 */
	public function getDataItems() {

		if ( !$this->isValid() ) {
			return [];
		}

		$dataItems = [];
		$index = 0;

		foreach ( $this->getPropertyDataItems() as $diProperty ) {
			$values = $this->getDataItem()->getSemanticData()->getPropertyValues( $diProperty );
			$dataItems[$index] = count( $values ) > 0 ? reset( $values ) : null;
			$index++;
		}

		return $dataItems;
	}

	/**
	 * @note called by SMWResultArray::loadContent for matching an index as denoted
	 * in |?Foo=Bar|+index=1 OR |?Foo=Bar|+index=Bar
	 *
	 * @see https://www.semantic-mediawiki.org/wiki/Help:Type_Record#Semantic_search
	 *
	 * @since 2.5
	 *
	 * @param string|integer $index
	 *
	 * @return DataItem[]|null
	 */
	public function getDataItemByIndex( $index ) {

		if ( is_numeric( $index ) ) {
			$pos = $index - 1;
			$dataItems = $this->getDataItems();
			return isset( $dataItems[$pos] ) ? $dataItems[$pos] : null;
		}

		if ( ( $property = $this->getPropertyDataItemByIndex( $index ) ) !== null ) {
			$values = $this->getDataItem()->getSemanticData()->getPropertyValues( $property );
			return reset( $values );
		}

		return null;
	}

	/**
	 * @note called by SMWResultArray::getNextDataValue to match an index
	 * that has been denoted using |?Foo=Bar|+index=1 OR |?Foo=Bar|+index=Bar
	 *
	 * @since 2.5
	 *
	 * @param string|integer $index
	 *
	 * @return DIProperty|null
	 */
	public function getPropertyDataItemByIndex( $index ) {

		$properties = $this->getPropertyDataItems();

		if ( is_numeric( $index ) ) {
			$pos = $index - 1;
			return isset( $properties[$pos] ) ? $properties[$pos] : null;
		}

		foreach ( $properties as $property ) {
			if ( $property->getLabel() === $index ) {
				return $property;
			}
		}

		return null;
	}

	/**
	 * Return the array (list) of properties that the individual entries of
	 * this datatype consist of.
	 *
	 * @since 2.5
	 *
	 * @param DIProperty|null $property
	 *
	 * @return DIProperty[]|[]
	 */
	protected function getFieldProperties( DIProperty $property = null ) {

		if ( $property === null || $property->getDiWikiPage() === null ) {
			return [];
		}

		$dataItem = ApplicationFactory::getInstance()->getPropertySpecificationLookup()->getFieldListBy( $property );

		if ( !$dataItem ) {
			return [];
		}

		$propertyListValue = new PropertyListValue( '__pls' );
		$propertyListValue->setDataItem( $dataItem );

		if ( !$propertyListValue->isValid() ) {
			return [];
		}

		return $propertyListValue->getPropertyDataItems();
	}

}
