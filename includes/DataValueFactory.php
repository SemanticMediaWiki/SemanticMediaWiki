<?php

namespace SMW;

use SMWDataItem;
use SMWDIError;
use SMWErrorValue;
use SMWLanguage;
use SMWPropertyValue;

/**
 * Factory class for creating SMWDataValue objects for supplied types or
 * properties and data values.
 *
 * The class has the main entry point newTypeIdValue(), which creates a new
 * datavalue object, possibly with preset user values, captions and
 * property names. To create suitable datavalues for a given property, the
 * method newPropertyObjectValue() can be used.
 *
 * @ingroup SMWDataValues
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class DataValueFactory {

	/** @var DataTypeRegistry */
	protected $dataTypeRegistry = null;

	/**
	 * FIXME In order to remove wfMessage dependency, a message formatter
	 * should be injected
	 *
	 * @note The constructor is made protected until all static/deprecated
	 * methods are removed
	 *
	 * @since 1.9
	 *
	 * @param DataTypeRegistry|null $dataTypeRegistry
	 */
	protected function __construct( DataTypeRegistry $dataTypeRegistry = null ) {
		$this->dataTypeRegistry = $dataTypeRegistry;
	}

	/**
	 * @since 1.9
	 *
	 * @return DataValueFactory
	 */
	public static function getInstance() {
		return new self( DataTypeRegistry::getInstance() );
	}

	/**
	 * Create a value from a type id. If no $value is given, an empty
	 * container is created, the value of which can be set later on.
	 *
	 * @param $typeId string id string for the given type
	 * @param $valueString mixed user value string, or false if unknown
	 * @param $caption mixed user-defined caption, or false if none given
	 * @param $property SMWDIProperty property object for which this value is made, or null
	 * @param $contextPage SMWDIWikiPage that provides a context for parsing the value string, or null
	 *
	 * @return SMWDataValue
	 */
	public static function newTypeIdValue( $typeId, $valueString = false, $caption = false,
			$property = null, $contextPage = null ) {

		$dataTypeRegistry = DataTypeRegistry::getInstance();

		if ( $dataTypeRegistry->hasDataTypeClassById( $typeId ) ) {
			$class  = $dataTypeRegistry->getDataTypeClassById( $typeId );
			$result = new $class( $typeId );
		} else {
			return new SMWErrorValue( $typeId,
				wfMessage( 'smw_unknowntype', $typeId )->inContentLanguage()->text(),
				$valueString, $caption );
		}

		if ( !is_null( $property ) ) {
			$result->setProperty( $property );
		}

		if ( !is_null( $contextPage ) ) {
			$result->setContextPage( $contextPage );
		}

		if ( $valueString !== false ) {
			$result->setUserValue( $valueString, $caption );
		}

		return $result;
	}

	/**
	 * Create a value for a data item.
	 *
	 * @param $dataItem SMWDataItem
	 * @param $property mixed null or SMWDIProperty property object for which this value is made
	 * @param $caption mixed user-defined caption, or false if none given
	 *
	 * @return SMWDataValue
	 */
	public static function newDataItemValue( SMWDataItem $dataItem, DIProperty $property = null, $caption = false ) {

		if ( $property !== null ) {
			$typeId = $property->findPropertyTypeID();
		} else {
			$typeId = DataTypeRegistry::getInstance()->getDefaultDataItemTypeId( $dataItem->getDiType() );
		}

		$result = self::newTypeIdValue( $typeId, false, $caption, $property );
		$result->setDataItem( $dataItem );

		if ( $caption !== false ) {
			$result->setCaption( $caption );
		}

		return $result;
	}

	/**
	 * Create a value for the given property, provided as an SMWDIProperty
	 * object. If no value is given, an empty container is created, the
	 * value of which can be set later on.
	 *
	 * @param $property SMWDIProperty property object for which this value is made
	 * @param $valueString mixed user value string, or false if unknown
	 * @param $caption mixed user-defined caption, or false if none given
	 * @param $contextPage SMWDIWikiPage that provides a context for parsing the value string, or null
	 *
	 * @return SMWDataValue
	 */
	public static function newPropertyObjectValue( DIProperty $property, $valueString = false,
			$caption = false, $contextPage = null ) {

		$typeId = $property->isInverse() ? '_wpg' : $property->findPropertyTypeID();
		return self::newTypeIdValue( $typeId, $valueString, $caption, $property, $contextPage );
	}

	/**
	 * This factory method returns a data value object from a given property,
	 * value string. It is intended to be used on user input to allow to
	 * turn a property and value strings into a data value object.
	 *
	 * @since 1.9
	 *
	 * @param string $propertyName property string
	 * @param string $valueString user value string
	 * @param mixed $caption user-defined caption
	 * @param SMWDIWikiPage|null $contextPage context for parsing the value string
	 *
	 * @return SMWDataValue
	 */
	public function newPropertyValue( $propertyName, $valueString,
		$caption = false, DIWikiPage $contextPage = null ) {

		Profiler::In( __METHOD__, true );

		$propertyDV = SMWPropertyValue::makeUserProperty( $propertyName );

		if ( !$propertyDV->isValid() ) {
			Profiler::Out( __METHOD__, true );
			return $propertyDV;
		}

		$propertyDI = $propertyDV->getDataItem();

		if ( $propertyDI instanceof SMWDIError ) {
			Profiler::Out( __METHOD__, true );
			return $propertyDV;
		}

		if ( $propertyDI instanceof DIProperty && !$propertyDI->isInverse() ) {
			$dataValue = self::newPropertyObjectValue(
				$propertyDI,
				$valueString,
				$caption,
				$contextPage
			);
		} else if ( $propertyDI instanceof DIProperty && $propertyDI->isInverse() ) {
			$dataValue = new SMWErrorValue( $propertyDV->getPropertyTypeID(),
				wfMessage( 'smw_noinvannot' )->inContentLanguage()->text(),
				$valueString, $caption
			);
		} else {
			$dataValue = new SMWErrorValue( $propertyDV->getPropertyTypeID(),
				wfMessage( 'smw-property-name-invalid', $propertyName )->inContentLanguage()->text(),
				$valueString, $caption
			);
		}

		Profiler::Out( __METHOD__, true );
		return $dataValue;
	}

	/**
	 * Deprecated since 1.9 and be removed in 1.10, use DataTypeRegistry instead
	 *
	 * @since  1.9
	 */
	public static function registerDatatype( $id, $className, $dataItemId, $label = false ) {
		DataTypeRegistry::getInstance()->registerDataType( $id, $className, $dataItemId, $label );
	}

	/**
	 * Deprecated since 1.9 and be removed in 1.10, use DataTypeRegistry instead
	 *
	 * @since  1.9
	 */
	public static function registerDatatypeAlias( $id, $label ) {
		DataTypeRegistry::getInstance()->registerDataTypeAlias( $id, $label );
	}

	/**
	 * Deprecated since 1.9 and be removed in 1.10, use DataTypeRegistry instead
	 *
	 * @since  1.9
	 */
	public static  function findTypeID( $label ) {
		return DataTypeRegistry::getInstance()->findTypeId( $label );
	}

	/**
	 * Deprecated since 1.9 and be removed in 1.10, use DataTypeRegistry instead
	 *
	 * @since  1.9
	 */
	public static function findTypeLabel( $id ) {
		return DataTypeRegistry::getInstance()->findTypeLabel( $id );
	}

	/**
	 * Deprecated since 1.9 and be removed in 1.10, use DataTypeRegistry instead
	 *
	 * @since  1.9
	 */
	public static function getKnownTypeLabels() {
		return DataTypeRegistry::getInstance()->getKnownTypeLabels();
	}

	/**
	 * Deprecated since 1.9 and be removed in 1.10, use DataTypeRegistry instead
	 *
	 * @since  1.9
	 */
	public static function getDataItemId( $typeId ) {
		return DataTypeRegistry::getInstance()->getDataItemId( $typeId );
	}

}
