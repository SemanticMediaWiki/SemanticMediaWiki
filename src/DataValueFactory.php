<?php

namespace SMW;

use RuntimeException;
use SMW\DataItems\DataItem;
use SMW\DataItems\Error;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValues\DataValue;
use SMW\DataValues\ErrorValue;
use SMW\DataValues\PropertyValue;
use SMW\Localizer\Localizer;
use SMW\Services\DataValueServiceFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * Factory class for creating DataValue objects for supplied types or
 * properties and data values.
 *
 * The class has the main entry point newDataValueByType(), which creates a new
 * datavalue object, possibly with preset user values, captions and
 * property names. To create suitable datavalues for a given property, the
 * method newDataValueByProperty() can be used.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class DataValueFactory {

	private static ?DataValueFactory $instance = null;

	private DataTypeRegistry $dataTypeRegistry;

	private DataValueServiceFactory $dataValueServiceFactory;

	private int $featureSet = 0;

	private array $defaultOutputFormatters;

	private array $callables = [];

	/**
	 * @since 1.9
	 */
	protected function __construct(
		DataTypeRegistry $dataTypeRegistry,
		DataValueServiceFactory $dataValueServiceFactory
	) {
		$this->dataTypeRegistry = $dataTypeRegistry;
		$this->dataValueServiceFactory = $dataValueServiceFactory;
	}

	/**
	 * @since 1.9
	 */
	public static function getInstance(): DataValueFactory {
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

		self::$instance = $instance;
		return self::$instance;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param callable $callable
	 *
	 * @throws RuntimeException
	 */
	public function addCallable( $key, callable $callable ): void {
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
	public function clearCallable( $key ): void {
		unset( $this->callables[$key] );
	}

	/**
	 * @since 2.4
	 */
	public function clear(): void {
		$this->dataTypeRegistry->clear();
		$this->callables = [];
		self::$instance = null;
	}

	/**
	 * @since 3.1
	 */
	public function setFeatureSet( int $featureSet ): void {
		$this->featureSet = $featureSet;
	}

	/**
	 * @since 3.0
	 */
	public function setDefaultOutputFormatters( array $defaultOutputFormatters ): void {
		$this->defaultOutputFormatters = [];

		foreach ( $defaultOutputFormatters as $type => $formatter ) {

			$type = str_replace( ' ', '_', $type );

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
	 * @param string $typeId id string for the given type
	 * @param string|false $valueString user value string, or false if unknown
	 * @param string|false $caption user-defined caption, or false if none given
	 * @param Property|null $property property object for which this value is made, or null
	 * @param WikiPage|null $contextPage that provides a context for parsing the value string, or null
	 *
	 * @return DataValue|ErrorValue
	 */
	public function newDataValueByType(
		$typeId,
		$valueString = false,
		$caption = false,
		?Property $property = null,
		$contextPage = null
	) {
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
	 * @param null $property mixed null or DIProperty property object for which this value is made
	 * @param $caption mixed user-defined caption, or false if none given
	 * @param WikiPage|null $contextPage
	 *
	 * @return DataValue|ErrorValue
	 */
	public function newDataValueByItem(
		DataItem $dataItem,
		?Property $property = null,
		$caption = false,
		$contextPage = null
	) {
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
	 * Create a value for the given property, provided as an DIProperty
	 * object. If no value is given, an empty container is created, the
	 * value of which can be set later on.
	 *
	 * @param $property DIProperty property object for which this value is made
	 * @param $valueString mixed user value string, or false if unknown
	 * @param $caption mixed user-defined caption, or false if none given
	 * @param null $contextPage SMWDIWikiPage that provides a context for parsing the value string, or null
	 *
	 * @return DataValue|ErrorValue
	 */
	public function newDataValueByProperty(
		Property $property,
		$valueString = false,
		$caption = false,
		$contextPage = null
	) {
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
	 * @param WikiPage|null $contextPage context for parsing the value string
	 *
	 * @return DataValue
	 */
	public function newDataValueByText(
		$propertyName,
		$valueString,
		$caption = false,
		?WikiPage $contextPage = null
	) {
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

			if ( $propertyDV->getDataItem() instanceof Property ) {
				$dataValue->setProperty( $propertyDV->getDataItem() );
			}

			return $dataValue;
		}

		$propertyDI = $propertyDV->getDataItem();

		if ( $propertyDI instanceof Error ) {
			return $propertyDV;
		}

		if ( $propertyDI instanceof Property && !$propertyDI->isInverse() ) {
			$dataValue = $this->newDataValueByProperty(
				$propertyDI,
				$valueString,
				$caption,
				$contextPage
			);

			$dataValue->setProperty( $propertyDV->getDataItem() );

		} elseif ( $propertyDI instanceof Property && $propertyDI->isInverse() ) {
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
	 * @param WikiPage|null $contextPage
	 *
	 * @return DataValue|ErrorValue
	 */
	public function newPropertyValueByLabel(
		$propertyLabel,
		$caption = false,
		?WikiPage $contextPage = null
	) {
		return $this->newDataValueByType(
			PropertyValue::TYPE_ID,
			$propertyLabel,
			$caption,
			null,
			$contextPage
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param Property $property
	 * @param string|false $caption
	 * @param WikiPage|null $contextPage
	 *
	 * @return DataValue|ErrorValue
	 */
	public function newPropertyValueByItem(
		Property $property,
		$caption = false,
		?WikiPage $contextPage = null
	) {
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
	 * @param string|false $caption
	 *
	 * @return ErrorValue
	 */
	public function newErrorValue(
		$typeid,
		$errormsg = '',
		$uservalue = '',
		$caption = false
	): ErrorValue {
		return new ErrorValue( $typeid, $errormsg, $uservalue, $caption );
	}

}
