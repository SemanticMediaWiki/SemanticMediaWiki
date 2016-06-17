<?php

namespace SMW;

use SMW\DataValues\ValueValidatorRegistry;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIError;
use SMWErrorValue as ErrorValue;

/**
 * Factory class for creating SMWDataValue objects for supplied types or
 * properties and data values.
 *
 * The class has the main entry point newTypeIdValue(), which creates a new
 * datavalue object, possibly with preset user values, captions and
 * property names. To create suitable datavalues for a given property, the
 * method newDataValueByProperty() can be used.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class DataValueFactory {

	/**
	 * @var DataTypeRegistry
	 */
	private static $instance = null;

	/**
	 * @var DataTypeRegistry
	 */
	private $dataTypeRegistry = null;

	/**
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

		if ( self::$instance === null ) {
			self::$instance = new self(
				DataTypeRegistry::getInstance()
			);
		}

		return self::$instance;
	}

	/**
	 * @since 2.4
	 */
	public function clear() {
		$this->dataTypeRegistry->clear();
		ValueValidatorRegistry::getInstance()->clear();
		self::$instance = null;
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
	 * @return DataValue
	 */
	public function newDataValueByType( $typeId, $valueString = false, $caption = false, DIProperty $property = null, $contextPage = null ) {

		$dataTypeRegistry = $this->dataTypeRegistry;

		if ( !$dataTypeRegistry->hasDataTypeClassById( $typeId ) ) {
			return new ErrorValue(
				$typeId,
				wfMessage( 'smw_unknowntype', $typeId )->inContentLanguage()->text(),
				$valueString,
				$caption
			);
		}

		$class  = $dataTypeRegistry->getDataTypeClassById( $typeId );
		$dataValue = new $class( $typeId );

		$dataValue->setExtraneousFunctions(
			$dataTypeRegistry->getExtraneousFunctions()
		);

		$dataValue->setOptions(
			$dataTypeRegistry->getOptions()
		);

		if ( $property !== null ) {
			$dataValue->setProperty( $property );
		}

		if ( !is_null( $contextPage ) ) {
			$dataValue->setContextPage( $contextPage );
		}

		$dataValue->setOption(
			DataValue::OPT_USER_LANGUAGE,
			Localizer::getInstance()->getUserLanguage()->getCode()
		);

		if ( $valueString !== false ) {
			$dataValue->setUserValue( $valueString, $caption );
		}

		return $dataValue;
	}

	/**
	 * Create a value for a data item.
	 *
	 * @param $dataItem DataItem
	 * @param $property mixed null or SMWDIProperty property object for which this value is made
	 * @param $caption mixed user-defined caption, or false if none given
	 *
	 * @return DataValue
	 */
	public function newDataValueByItem( DataItem $dataItem, DIProperty $property = null, $caption = false ) {

		if ( $property !== null ) {
			$typeId = $property->findPropertyTypeID();
		} else {
			$typeId = $this->dataTypeRegistry->getDefaultDataItemTypeId( $dataItem->getDiType() );
		}

		$dataValue = $this->newDataValueByType( $typeId, false, $caption, $property );
		$dataValue->setDataItem( $dataItem );

		if ( $caption !== false ) {
			$dataValue->setCaption( $caption );
		}

		return $dataValue;
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
	 * @return DataValue
	 */
	public function newDataValueByProperty( DIProperty $property, $valueString = false, $caption = false, $contextPage = null ) {

		$typeId = $property->isInverse() ? '_wpg' : $property->findPropertyTypeID();

		return $this->newDataValueByType( $typeId, $valueString, $caption, $property, $contextPage );
	}

	/**
	 * This factory method returns a data value object from a given property,
	 * value string. It is intended to be used on user input to allow to
	 * turn a property and value string into a data value object.
	 *
	 * @since 1.9
	 *
	 * @param string $propertyName property string
	 * @param string $valueString user value string
	 * @param mixed $caption user-defined caption
	 * @param SMWDIWikiPage|null $contextPage context for parsing the value string
	 *
	 * @return DataValue
	 */
	public function newDataValueByText( $propertyName, $valueString, $caption = false, DIWikiPage $contextPage = null ) {

		// Enforce upper case for the first character on annotations that are used
		// within the property namespace in order to avoid confusion when
		// $wgCapitalLinks setting is disabled
		if ( $contextPage !== null && $contextPage->getNamespace() === SMW_NS_PROPERTY ) {
			$propertyName = ucfirst( $propertyName );
		}

		$propertyDV = $this->newPropertyValueByLabel( $propertyName );

		if ( !$propertyDV->isValid() ) {
			return $propertyDV;
		}

		if ( !$propertyDV->canUse() ) {
			$dataValue = new ErrorValue(
				$propertyDV->getPropertyTypeID(),
				wfMessage( 'smw-datavalue-property-restricted-use', $propertyName )->inContentLanguage()->text(),
				$valueString,
				$caption
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );

			return $dataValue;
		}

		$propertyDI = $propertyDV->getDataItem();

		if ( $propertyDI instanceof SMWDIError ) {
			return $propertyDV;
		}

		if ( $propertyDI instanceof DIProperty && !$propertyDI->isInverse() ) {
			$dataValue = $this->newDataValueByProperty(
				$propertyDI,
				$valueString,
				$caption,
				$contextPage
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );

		} elseif ( $propertyDI instanceof DIProperty && $propertyDI->isInverse() ) {
			$dataValue = new ErrorValue( $propertyDV->getPropertyTypeID(),
				wfMessage( 'smw_noinvannot' )->inContentLanguage()->text(),
				$valueString,
				$caption
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );
		} else {
			$dataValue = new ErrorValue(
				$propertyDV->getPropertyTypeID(),
				wfMessage( 'smw-property-name-invalid', $propertyName )->inContentLanguage()->text(),
				$valueString,
				$caption
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );
		}

		if ( $dataValue->isValid() && !$dataValue->canUse() ) {
			$dataValue = new ErrorValue(
				$propertyDV->getPropertyTypeID(),
				wfMessage( 'smw-datavalue-restricted-use', implode( ',', $dataValue->getErrors() ) )->inContentLanguage()->text(),
				$valueString,
				$caption
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );
		}

		return $dataValue;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $propertyLabel
	 *
	 * @return DataValue
	 */
	public function newPropertyValueByLabel( $propertyLabel ) {
		return $this->newDataValueByType( '__pro', $propertyLabel );
	}

/// Deprecated methods

	/**
	 * @deprecated since 2.4, use DataValueFactory::newDataValueByItem
	 *
	 * @return DataValue
	 */
	public static function newDataItemValue( DataItem $dataItem, DIProperty $property = null, $caption = false ) {
		return self::getInstance()->newDataValueByItem( $dataItem, $property, $caption );
	}

	/**
	 * @deprecated since 2.4, use DataValueFactory::newDataValueByProperty
	 *
	 * @return DataValue
	 */
	public static function newPropertyObjectValue( DIProperty $property, $valueString = false, $caption = false, $contextPage = null ) {
		return self::getInstance()->newDataValueByProperty( $property, $valueString, $caption, $contextPage );
	}

	/**
	 * @deprecated since 2.4, use DataValueFactory::newDataValueByType
	 *
	 * @return DataValue
	 */
	public static function newTypeIdValue( $typeId, $valueString = false, $caption = false, DIProperty $property = null, $contextPage = null ) {
		return self::getInstance()->newDataValueByType( $typeId, $valueString, $caption, $property, $contextPage );
	}

	/**
	 * @deprecated since 2.4, use DataTypeRegistry::newDataValueByText
	 *
	 * @return DataValue
	 */
	public function newPropertyValue( $propertyName, $valueString, $caption = false, DIWikiPage $contextPage = null ) {
		return $this->newDataValueByText( $propertyName, $valueString, $caption, $contextPage );
	}

	/**
	 * @deprecated since 1.9, use DataTypeRegistry::registerDataType
	 */
	public static function registerDatatype( $id, $className, $dataItemId, $label = false ) {
		DataTypeRegistry::getInstance()->registerDataType( $id, $className, $dataItemId, $label );
	}

	/**
	 * @deprecated since 1.9, use DataTypeRegistry::registerDataTypeAlias
	 */
	public static function registerDatatypeAlias( $id, $label ) {
		DataTypeRegistry::getInstance()->registerDataTypeAlias( $id, $label );
	}

	/**
	 * @deprecated since 1.9, use DataTypeRegistry::findTypeId
	 */
	public static function findTypeID( $label ) {
		return DataTypeRegistry::getInstance()->findTypeId( $label );
	}

	/**
	 * @deprecated since 1.9, use DataTypeRegistry::findTypeLabel
	 */
	public static function findTypeLabel( $id ) {
		return DataTypeRegistry::getInstance()->findTypeLabel( $id );
	}

	/**
	 * @deprecated since 1.9, use DataTypeRegistry::getKnownTypeLabels
	 */
	public static function getKnownTypeLabels() {
		return DataTypeRegistry::getInstance()->getKnownTypeLabels();
	}

	/**
	 * @deprecated since 1.9, use DataTypeRegistry::getDataItemId
	 */
	public static function getDataItemId( $typeId ) {
		return DataTypeRegistry::getInstance()->getDataItemId( $typeId );
	}

}
