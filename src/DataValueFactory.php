<?php

namespace SMW;

use SMW\DataValues\PropertyValue;
use SMW\Services\DataValueServiceFactory;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIError;
use SMWErrorValue as ErrorValue;
use RuntimeException;

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
	 * @var DataValueFactory
	 */
	private static $instance;

	/**
	 * @var DataTypeRegistry
	 */
	private $dataTypeRegistry;

	/**
	 * @var DataValueServiceFactory
	 */
	private $dataValueServiceFactory;

	/**
	 * @var integer
	 */
	private $featureSet = 0;

	/**
	 * @var array
	 */
	private $defaultOutputFormatters;

	/**
	 * @var []
	 */
	private $callables = [];

	/**
	 * @since 1.9
	 *
	 * @param DataTypeRegistry $dataTypeRegistry
	 * @param DataValueServiceFactory $dataValueServiceFactory
	 */
	protected function __construct( DataTypeRegistry $dataTypeRegistry, DataValueServiceFactory $dataValueServiceFactory ) {
		$this->dataTypeRegistry = $dataTypeRegistry;
		$this->dataValueServiceFactory = $dataValueServiceFactory;
	}

	/**
	 * @since 1.9
	 *
	 * @return DataValueFactory
	 */
	public static function getInstance() {

		if ( self::$instance !== null ) {
			return self::$instance;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$dataValueServiceFactory = $applicationFactory->create( 'DataValueServiceFactory' );
		$dataTypeRegistry = DataTypeRegistry::getInstance();

		$instance = new self(
			$dataTypeRegistry,
			$dataValueServiceFactory
		);

		$instance->setFeatureSet(
			$settings->get( 'smwgDVFeatures' )
		);

		$instance->setDefaultOutputFormatters(
			$settings->get( 'smwgDefaultOutputFormatters' )
		);

		return self::$instance = $instance;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param callable $callable
	 *
	 * @throws RuntimeException
	 */
	public function addCallable( $key, callable $callable ) {

		if ( isset( $this->callables[$key] ) ) {
			throw new RuntimeException( "`$key` is already in use, please clear the callable first!" );
		}

		$this->callables[$key] = $callable;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 */
	public function clearCallable( $key ) {
		unset( $this->callables[$key] );
	}

	/**
	 * @since 2.4
	 */
	public function clear() {
		$this->dataTypeRegistry->clear();
		$this->callables = [];
		self::$instance = null;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $featureSet
	 */
	public function setFeatureSet( $featureSet ) {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $defaultOutputFormatters
	 */
	public function setDefaultOutputFormatters( array $defaultOutputFormatters ) {

		$this->defaultOutputFormatters = [];

		foreach ( $defaultOutputFormatters as $type => $formatter ) {

			$type = str_replace( ' ' , '_', $type );

			if ( $type[0] !== '_' && ( $dType = $this->dataTypeRegistry->findTypeByLabel( $type ) ) !== '' ) {
				$type = $dType;
			}

			$this->defaultOutputFormatters[$type] = $formatter;
		}
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

		if ( !$this->dataTypeRegistry->hasDataTypeClassById( $typeId ) ) {
			return new ErrorValue(
				$typeId,
				[ 'smw_unknowntype', $typeId ],
				$valueString,
				$caption
			);
		}

		$dataValue = $this->dataValueServiceFactory->newDataValueByTypeOrClass(
			$typeId,
			$this->dataTypeRegistry->getDataTypeClassById( $typeId )
		);

		$dataValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$dataValue->setOption( 'smwgDVFeatures', $this->featureSet );

		foreach ( $this->callables as $key => $callable ) {
			$dataValue->addCallable( $key, $callable );
		}

		foreach ( $this->dataTypeRegistry->getCallablesByTypeId( $typeId ) as $key => $value ) {
			$dataValue->addCallable( $key, $value );
		}

		$localizer = Localizer::getInstance();

		$dataValue->setOption(
			DataValue::OPT_USER_LANGUAGE,
			$localizer->getUserLanguage()->getCode()
		);

		$dataValue->setOption(
			DataValue::OPT_CONTENT_LANGUAGE,
			$localizer->getContentLanguage()->getCode()
		);

		$dataValue->setOption(
			DataValue::OPT_COMPACT_INFOLINKS,
			$GLOBALS['smwgCompactLinkSupport']
		);

		if ( isset( $this->defaultOutputFormatters[$typeId] ) ) {
			$dataValue->setOutputFormat( $this->defaultOutputFormatters[$typeId] );
		}

		if ( $property !== null ) {
			$dataValue->setProperty( $property );

			if ( isset( $this->defaultOutputFormatters[$property->getKey()] ) ) {
				$dataValue->setOutputFormat( $this->defaultOutputFormatters[$property->getKey()] );
			}
		}

		if ( $contextPage !== null ) {
			$dataValue->setContextPage( $contextPage );
		}

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
	 * @param DIWikiPage|null $contextPage
	 *
	 * @return DataValue
	 */
	public function newDataValueByItem( DataItem $dataItem, DIProperty $property = null, $caption = false, $contextPage = null ) {

		if ( $property !== null ) {
			$typeId = $property->findPropertyTypeID();
		} else {
			$typeId = $this->dataTypeRegistry->getDefaultDataItemByType( $dataItem->getDiType() );
		}

		$dataValue = $this->newDataValueByType(
			$typeId,
			false,
			$caption,
			$property,
			$contextPage
		);

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

		$propertyDV = $this->newPropertyValueByLabel( $propertyName, $caption, $contextPage );

		if ( !$propertyDV->isValid() ) {
			return $propertyDV;
		}

		if ( $propertyDV->isRestricted() ) {
			$dataValue = new ErrorValue(
				$propertyDV->getPropertyTypeID(),
				$propertyDV->getRestrictionError(),
				$valueString,
				$caption
			);

			if ( $propertyDV->getDataItem() instanceof DIProperty ) {
				$dataValue->setProperty( $propertyDV->getDataItem() );
			}

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
				[ 'smw_noinvannot' ],
				$valueString,
				$caption
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );
		} else {
			$dataValue = new ErrorValue(
				$propertyDV->getPropertyTypeID(),
				[ 'smw-property-name-invalid', $propertyName ],
				$valueString,
				$caption
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );
		}

		if ( $dataValue->isValid() && !$dataValue->canUse() ) {
			$dataValue = new ErrorValue(
				$propertyDV->getPropertyTypeID(),
				[ 'smw-datavalue-restricted-use', implode( ',', $dataValue->getErrors() ) ],
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
	 * @param string|false $caption
	 * @param DIWikiPage|null $contextPage
	 *
	 * @return DataValue
	 */
	public function newPropertyValueByLabel( $propertyLabel, $caption = false, DIWikiPage $contextPage = null ) {
		return $this->newDataValueByType( PropertyValue::TYPE_ID, $propertyLabel, $caption, null, $contextPage );
	}

	/**
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 * @param string|false $caption
	 * @param DIWikiPage|null $contextPage
	 *
	 * @return DataValue
	 */
	public function newPropertyValueByItem( DIProperty $property, $caption = false, DIWikiPage $contextPage = null ) {

		$dataValue = $this->newDataValueByType(
			PropertyValue::TYPE_ID,
			false,
			$caption,
			null,
			$contextPage
		);

		$dataValue->setDataItem( $property );

		if ( $caption !== false ) {
			$dataValue->setCaption( $caption );
		}

		return $dataValue;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $typeid
	 * @param string|array $errormsg
	 * @param string $uservalue
	 * @param string $caption
	 *
	 * @return ErrorValue
	 */
	public function newErrorValue( $typeid, $errormsg = '', $uservalue = '', $caption = false ) {
		return new ErrorValue( $typeid, $errormsg, $uservalue, $caption );
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

}
