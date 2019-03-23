<?php

namespace SMW\Services;

use Onoi\CallbackContainer\ContainerBuilder;
use SMW\DataValueFactory;
use SMW\DataValues\InfoLinksProvider;
use SMW\DataValues\StringValue;
use SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter;
use SMW\DataValues\ValueFormatters\NoValueFormatter;
use SMW\DataValues\ValueFormatters\ValueFormatter;
use SMW\DataValues\ValueParsers\ValueParser;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\PropertyRestrictionExaminer;
use SMW\PropertySpecificationLookup;
use SMWDataValue as DataValue;
use SMWNumberValue as NumberValue;
use SMWTimeValue as TimeValue;

/**
 * @private
 *
 * This class provides service and factory functions for DataValue objects and
 * are only to be used for those objects.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServiceFactory {

	/**
	 * Indicates a DataValue service
	 */
	const SERVICE_FILE = 'DataValueServices.php';

	/**
	 * Indicates a DataValue service
	 */
	const TYPE_INSTANCE = '__dv.';

	/**
	 * Indicates a ValueParser service
	 */
	const TYPE_PARSER = '__dv.parser.';

	/**
	 * Indicates a ValueFormatter service
	 */
	const TYPE_FORMATTER = '__dv.formatter.';

	/**
	 * Indicates a ValueValidator service
	 */
	const TYPE_VALIDATOR = '__dv.validator.';

	/**
	 * Extraneous service
	 */
	const TYPE_EXT_FUNCTION = '__dv.ext.func.';

	/**
	 * @var ContainerBuilder
	 */
	private $containerBuilder;

	/**
	 * @var DispatchingDataValueFormatter
	 */
	private $dispatchingDataValueFormatter = null;

	/**
	 * @since 2.5
	 */
	public function __construct( ContainerBuilder $containerBuilder ) {
		$this->containerBuilder = $containerBuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValue $dataValue
	 *
	 * @return InfoLinksProvider
	 */
	public function newInfoLinksProvider( DataValue $dataValue ) {
		return new InfoLinksProvider( $dataValue, $this->getPropertySpecificationLookup() );
	}

	/**
	 * @since 3.0
	 *
	 * @return DataValueFactory
	 */
	public function getDataValueFactory() {
		return DataValueFactory::getInstance();
	}

	/**
	 * Imported functions registered with DataTypeRegistry::registerExtraneousFunction
	 *
	 * @since 2.5
	 *
	 * @param array $extraneousFunctions
	 */
	public function importExtraneousFunctions( array $extraneousFunctions ) {
		foreach ( $extraneousFunctions as $serviceName => $calllback ) {
			$this->containerBuilder->registerCallback( self::TYPE_EXT_FUNCTION . $serviceName, $calllback );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param string $serviceName
	 *
	 * @return mixed
	 */
	public function newExtraneousFunctionByName( $serviceName ) {
		return $this->containerBuilder->create( self::TYPE_EXT_FUNCTION . $serviceName );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $typeId
	 * @param string $class
	 *
	 * @return DataValue
	 */
	public function newDataValueByType( $typeId, $class ) {

		if ( $this->containerBuilder->isRegistered( self::TYPE_INSTANCE . $typeId ) ) {
			return $this->containerBuilder->create( self::TYPE_INSTANCE . $typeId );
		}

		// Legacy invocation, for those that have not been defined yet!s
		return new $class( $typeId );
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValue $dataValue
	 *
	 * @return ValueParser
	 */
	public function getValueParser( DataValue $dataValue ) {
		return $this->containerBuilder->singleton( self::TYPE_PARSER . $dataValue->getTypeID() );
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValue $dataValue
	 *
	 * @return ValueFormatter
	 */
	public function getValueFormatter( DataValue $dataValue ) {

		$id = self::TYPE_FORMATTER . $dataValue->getTypeID();

		if ( $this->containerBuilder->isRegistered( $id ) ) {
			$dataValueFormatter = $this->containerBuilder->singleton( $id );
		} else {
			$dataValueFormatter = $this->getDispatchableValueFormatter( $dataValue );
		}

		$dataValueFormatter->setDataValue(
			$dataValue
		);

		return $dataValueFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * @return ConstraintValueValidator
	 */
	public function getConstraintValueValidator() {
		return $this->containerBuilder->singleton( self::TYPE_VALIDATOR . 'CompoundConstraintValueValidator' );
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertySpecificationLookup
	 */
	public function getPropertySpecificationLookup() {
		return $this->containerBuilder->singleton( 'PropertySpecificationLookup' );
	}

	/**
	 * @since 3.1
	 *
	 * @return UnitConverter
	 */
	public function getUnitConverter() {
		return $this->containerBuilder->singleton( 'UnitConverter' );
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertyRestrictionExaminer
	 */
	public function getPropertyRestrictionExaminer() {

		$propertyRestrictionExaminer = $this->containerBuilder->singleton( 'PropertyRestrictionExaminer' );

		$propertyRestrictionExaminer->setUser(
			$GLOBALS['wgUser']
		);

		return $propertyRestrictionExaminer;
	}


	/**
	 * @since 3.1
	 *
	 * @return DescriptionBuilderRegistry
	 */
	public function getDescriptionBuilderRegistry() {
		return $this->containerBuilder->singleton( 'DescriptionBuilderRegistry' );
	}

	private function getDispatchableValueFormatter( $dataValue ) {

		if ( $this->dispatchingDataValueFormatter === null ) {
			$this->dispatchingDataValueFormatter = $this->newDispatchingDataValueFormatter();
		}

		return $this->dispatchingDataValueFormatter->getDataValueFormatterFor( $dataValue );
	}

	private function newDispatchingDataValueFormatter() {

		$dispatchingDataValueFormatter = new DispatchingDataValueFormatter();

		// To be checked only after DispatchingDataValueFormatter::addDataValueFormatter did
		// not match any previous registered DataValueFormatters
		$dispatchingDataValueFormatter->addDefaultDataValueFormatter(
			$this->containerBuilder->singleton( self::TYPE_FORMATTER . StringValue::TYPE_ID )
		);

		$dispatchingDataValueFormatter->addDefaultDataValueFormatter(
			$this->containerBuilder->singleton( self::TYPE_FORMATTER . NumberValue::TYPE_ID )
		);

		$dispatchingDataValueFormatter->addDefaultDataValueFormatter(
			$this->containerBuilder->singleton( self::TYPE_FORMATTER . TimeValue::TYPE_ID )
		);

		$dispatchingDataValueFormatter->addDefaultDataValueFormatter( new NoValueFormatter() );

		return $dispatchingDataValueFormatter;
	}

}
