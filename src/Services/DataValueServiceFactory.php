<?php

namespace SMW\Services;

use MediaWiki\Context\RequestContext;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\DataValues\InfoLinksProvider;
use SMW\DataValues\Number\UnitConverter;
use SMW\DataValues\NumberValue;
use SMW\DataValues\StringValue;
use SMW\DataValues\TimeValue;
use SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter;
use SMW\DataValues\ValueFormatters\NoValueFormatter;
use SMW\DataValues\ValueFormatters\ValueFormatter;
use SMW\DataValues\ValueParsers\ValueParser;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\Property\RestrictionExaminer;
use SMW\Property\SpecificationLookup;
use SMW\Query\DescriptionBuilderRegistry;
use SMW\Store;

/**
 * @private
 *
 * This class provides service and factory functions for DataValue objects and
 * are only to be used for those objects.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataValueServiceFactory {

	/**
	 * Indicates a DataValue service
	 */
	const SERVICE_FILE = 'datavalues.php';

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

	private ?DispatchingDataValueFormatter $dispatchingDataValueFormatter = null;

	/**
	 * @since 2.5
	 */
	public function __construct( private readonly ServicesContainer $servicesContainer ) {
	}

	/**
	 * Builds a `ServicesContainer` seeded with the DataValue domain services
	 * defined in the `datavalues.php` wiring file.
	 *
	 * The `StringValue` legacy id alias is preserved by registering the same
	 * callback under both the canonical and the legacy formatter key.
	 *
	 * @since 7.0.0
	 */
	public static function newServicesContainer( string $servicesFileDir ): ServicesContainer {
		$servicesContainer = new ServicesContainer();

		$services = require $servicesFileDir . '/' . self::SERVICE_FILE;

		foreach ( $services as $key => $callback ) {
			$servicesContainer->add( $key, $callback );
		}

		// Preserve the legacy StringValue formatter id by delegating to the primary
		// key's singleton, so both keys resolve to the same shared instance.
		$servicesContainer->add(
			self::TYPE_FORMATTER . StringValue::TYPE_LEGACY_ID,
			static fn ( ServicesContainer $container ) => $container->singleton(
				self::TYPE_FORMATTER . StringValue::TYPE_ID,
				$container
			)
		);

		return $servicesContainer;
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValue $dataValue
	 *
	 * @return InfoLinksProvider
	 */
	public function newInfoLinksProvider( DataValue $dataValue ): InfoLinksProvider {
		return new InfoLinksProvider( $dataValue, $this->getPropertySpecificationLookup() );
	}

	/**
	 * @since 3.0
	 *
	 * @return DataValueFactory
	 */
	public function getDataValueFactory(): DataValueFactory {
		return DataValueFactory::getInstance();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $typeId
	 * @param string $class
	 *
	 * @return DataValue
	 */
	public function newDataValueByTypeOrClass( $typeId, $class ) {
		if ( is_callable( $class ) ) {
			return $class( $typeId );
		}
		if ( $this->servicesContainer->isRegistered( $class ) ) {
			return $this->servicesContainer->create( $class, $this->servicesContainer );
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
		return $this->servicesContainer->singleton(
			self::TYPE_PARSER . $dataValue->getTypeID(),
			$this->servicesContainer
		);
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

		if ( $this->servicesContainer->isRegistered( $id ) ) {
			$dataValueFormatter = $this->servicesContainer->singleton( $id, $this->servicesContainer );
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
		return $this->servicesContainer->singleton(
			self::TYPE_VALIDATOR . 'CompoundConstraintValueValidator',
			$this->servicesContainer
		);
	}

	/**
	 * @since 2.5
	 *
	 * @return SpecificationLookup
	 */
	public function getPropertySpecificationLookup() {
		return ServicesFactory::getInstance()->singleton( 'PropertySpecificationLookup' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getStore(): Store {
		return ServicesFactory::getInstance()->getStore();
	}

	/**
	 * @since 3.1
	 *
	 * @return UnitConverter
	 */
	public function getUnitConverter() {
		return $this->servicesContainer->singleton( 'UnitConverter', $this->servicesContainer );
	}

	/**
	 * @since 3.0
	 *
	 * @return RestrictionExaminer
	 */
	public function getPropertyRestrictionExaminer() {
		$propertyRestrictionExaminer = ServicesFactory::getInstance()->singleton( 'PropertyRestrictionExaminer' );
		$propertyRestrictionExaminer->setUser( RequestContext::getMain()->getUser() );

		return $propertyRestrictionExaminer;
	}

	/**
	 * @since 3.1
	 *
	 * @return DescriptionBuilderRegistry
	 */
	public function getDescriptionBuilderRegistry() {
		return $this->servicesContainer->singleton( 'DescriptionBuilderRegistry', $this->servicesContainer );
	}

	private function getDispatchableValueFormatter( DataValue $dataValue ) {
		if ( $this->dispatchingDataValueFormatter === null ) {
			$this->dispatchingDataValueFormatter = $this->newDispatchingDataValueFormatter();
		}

		return $this->dispatchingDataValueFormatter->getDataValueFormatterFor( $dataValue );
	}

	private function newDispatchingDataValueFormatter(): DispatchingDataValueFormatter {
		$dispatchingDataValueFormatter = new DispatchingDataValueFormatter();

		// To be checked only after DispatchingDataValueFormatter::addDataValueFormatter did
		// not match any previous registered DataValueFormatters
		$dispatchingDataValueFormatter->addDefaultDataValueFormatter(
			$this->servicesContainer->singleton( self::TYPE_FORMATTER . StringValue::TYPE_ID, $this->servicesContainer )
		);

		$dispatchingDataValueFormatter->addDefaultDataValueFormatter(
			$this->servicesContainer->singleton( self::TYPE_FORMATTER . NumberValue::TYPE_ID, $this->servicesContainer )
		);

		$dispatchingDataValueFormatter->addDefaultDataValueFormatter(
			$this->servicesContainer->singleton( self::TYPE_FORMATTER . TimeValue::TYPE_ID, $this->servicesContainer )
		);

		$dispatchingDataValueFormatter->addDefaultDataValueFormatter( new NoValueFormatter() );

		return $dispatchingDataValueFormatter;
	}

}
