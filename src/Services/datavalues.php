<?php

namespace SMW\Services;

use MediaWiki\Logger\LoggerFactory;
use SMW\DataValues\AllowsListValue;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\ConstraintSchemaValue;
use SMW\DataValues\ImportValue;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\Number\UnitConverter;
use SMW\DataValues\NumberValue;
use SMW\DataValues\PropertyValue;
use SMW\DataValues\QuantityValue;
use SMW\DataValues\ReferenceValue;
use SMW\DataValues\StringValue;
use SMW\DataValues\TimeValue;
use SMW\DataValues\ValueFormatters\CodeStringValueFormatter;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueFormatters\NumberValueFormatter;
use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMW\DataValues\ValueFormatters\ReferenceValueFormatter;
use SMW\DataValues\ValueFormatters\StringValueFormatter;
use SMW\DataValues\ValueFormatters\TimeValueFormatter;
use SMW\DataValues\ValueParsers\AllowsListValueParser;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\ValueParsers\ImportValueParser;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;
use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataValues\ValueParsers\TimeValueParser;
use SMW\DataValues\ValueValidators\AllowsListConstraintValueValidator;
use SMW\DataValues\ValueValidators\CompoundConstraintValueValidator;
use SMW\DataValues\ValueValidators\ConstraintSchemaValueValidator;
use SMW\DataValues\ValueValidators\PatternConstraintValueValidator;
use SMW\DataValues\ValueValidators\PropertySpecificationConstraintValueValidator;
use SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator;
use SMW\Query\DescriptionBuilderRegistry;
use SMW\Site;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed via DataValueServiceFactory
 * with services being expected to require a prefix to match each individual instance
 * to a specific DataValue.
 *
 * Each callback receives the DataValue domain `ServicesContainer` so it can resolve
 * sibling datavalue services. Global SMW services are resolved through
 * `ServicesFactory::getInstance()`.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
return [

	/**
	 * @return UnitConverter
	 */
	'UnitConverter' => static function ( ServicesContainer $container ): UnitConverter {
		return new UnitConverter(
			ServicesFactory::getInstance()->singleton( 'PropertySpecificationLookup' ),
			ServicesFactory::getInstance()->singleton( 'EntityCache' )
		);
	},

	/**
	 * @return ConstraintSchemaValue
	 */
	ConstraintSchemaValue::class => static function ( ServicesContainer $container ): ConstraintSchemaValue {
		return new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			ServicesFactory::getInstance()->singleton( 'PropertySpecificationLookup' )
		);
	},

	/**
	 * @return PropertyValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . PropertyValue::TYPE_ID => static function ( ServicesContainer $container ): PropertyValueParser {
		$propertyValueParser = new PropertyValueParser();

		$propertyValueParser->setInvalidCharacterList(
			ServicesFactory::getInstance()->singleton( 'Settings' )->get( 'smwgPropertyInvalidCharacterList' )
		);

		$propertyValueParser->isCapitalLinks(
			Site::isCapitalLinks()
		);

		return $propertyValueParser;
	},

	/**
	 * @return PropertyValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . PropertyValue::TYPE_ID => static function ( ServicesContainer $container ): PropertyValueFormatter {
		$servicesFactory = ServicesFactory::getInstance();
		return new PropertyValueFormatter(
			$servicesFactory->singleton( 'PropertySpecificationLookup' ),
			$servicesFactory->getPropertyLabelFinder()
		);
	},

	/**
	 * @return AllowsPatternValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID => static function ( ServicesContainer $container ): AllowsPatternValueParser {
		return new AllowsPatternValueParser( ServicesFactory::getInstance()->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * @return AllowsListValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID => static function ( ServicesContainer $container ): AllowsListValueParser {
		return new AllowsListValueParser( ServicesFactory::getInstance()->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * @return CompoundConstraintValueValidator
	 */
	DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator' => static function ( ServicesContainer $container ): CompoundConstraintValueValidator {
		$servicesFactory = ServicesFactory::getInstance();

		$propertySpecificationLookup = $servicesFactory->singleton( 'PropertySpecificationLookup' );
		$store = $servicesFactory->singleton( 'Store' );
		$constraintFactory = $servicesFactory->singleton( 'ConstraintFactory' );

		$compoundConstraintValueValidator = new CompoundConstraintValueValidator();

		// Any registered ConstraintValueValidator becomes weaker(diminished) in the context
		// of a preceding validator
		$compoundConstraintValueValidator->registerConstraintValueValidator(
			new UniquenessConstraintValueValidator(
				$constraintFactory->newUniqueValueConstraint(),
				$propertySpecificationLookup
			)
		);

		$patternConstraintValueValidator = new PatternConstraintValueValidator(
			$container->create( DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID, $container ),
			$propertySpecificationLookup
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			$patternConstraintValueValidator
		);

		$allowsListConstraintValueValidator = new AllowsListConstraintValueValidator(
			$container->create( DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID, $container ),
			$propertySpecificationLookup
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			$allowsListConstraintValueValidator
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			new PropertySpecificationConstraintValueValidator()
		);

		$constraintSchemaValueValidator = new ConstraintSchemaValueValidator(
			$constraintFactory->newConstraintCheckRunner(),
			$servicesFactory->singleton( 'SchemaFactory' )->newSchemaFinder( $store ),
			$servicesFactory->getJobQueue()
		);

		$constraintSchemaValueValidator->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			$constraintSchemaValueValidator
		);

		$compoundConstraintValueValidator->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		return $compoundConstraintValueValidator;
	},

	/**
	 * @return ImportValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . ImportValue::TYPE_ID => static function ( ServicesContainer $container ): ImportValueParser {
		return new ImportValueParser( ServicesFactory::getInstance()->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * @return StringValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_ID => static function ( ServicesContainer $container ): StringValueFormatter {
		return new StringValueFormatter();
	},

	/**
	 * @return CodeStringValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_COD_ID => static function ( ServicesContainer $container ): CodeStringValueFormatter {
		return new CodeStringValueFormatter();
	},

	/**
	 * @return ReferenceValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . ReferenceValue::TYPE_ID => static function ( ServicesContainer $container ): ReferenceValueFormatter {
		return new ReferenceValueFormatter();
	},

	/**
	 * @return MonolingualTextValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . MonolingualTextValue::TYPE_ID => static function ( ServicesContainer $container ): MonolingualTextValueParser {
		return new MonolingualTextValueParser();
	},

	/**
	 * @return MonolingualTextValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . MonolingualTextValue::TYPE_ID => static function ( ServicesContainer $container ): MonolingualTextValueFormatter {
		return new MonolingualTextValueFormatter();
	},

	/**
	 * @return NumberValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . QuantityValue::TYPE_ID => static function ( ServicesContainer $container ): NumberValueFormatter {
		return $container->create( DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID, $container );
	},

	/**
	 * @return NumberValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID => static function ( ServicesContainer $container ): NumberValueFormatter {
		return new NumberValueFormatter();
	},

	/**
	 * @return TimeValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . TimeValue::TYPE_ID => static function ( ServicesContainer $container ): TimeValueFormatter {
		return new TimeValueFormatter();
	},

	/**
	 * @return TimeValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . TimeValue::TYPE_ID => static function ( ServicesContainer $container ): TimeValueParser {
		return new TimeValueParser();
	},

	/**
	 * @return DescriptionBuilderRegistry
	 */
	'DescriptionBuilderRegistry' => static function ( ServicesContainer $container ): DescriptionBuilderRegistry {
		return new DescriptionBuilderRegistry();
	},

];
