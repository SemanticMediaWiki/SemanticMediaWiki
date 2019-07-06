<?php

namespace SMW\Services;

use SMW\DataValues\AllowsListValue;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\ImportValue;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ReferenceValue;
use SMW\DataValues\StringValue;
use SMW\DataValues\ConstraintSchemaValue;
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
use SMW\DataValues\ValueValidators\PatternConstraintValueValidator;
use SMW\DataValues\ValueValidators\PropertySpecificationConstraintValueValidator;
use SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator;
use SMW\DataValues\ValueValidators\ConstraintSchemaValueValidator;
use SMW\Query\DescriptionBuilderRegistry;
use SMWNumberValue as NumberValue;
use SMWPropertyValue as PropertyValue;
use SMWQuantityValue as QuantityValue;
use SMWTimeValue as TimeValue;
use SMW\Site;
use SMW\DataValues\Number\UnitConverter;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed via DataValueServiceFactory
 * with services being expected to require a prefix to match each individual instance
 * to a specific DataValue.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
return [

	/**
	 * UnitConverter
	 *
	 * @return callable
	 */
	'UnitConverter' => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			'UnitConverter',
			UnitConverter::class
		);

		$unitConverter = new UnitConverter(
			$containerBuilder->singleton( 'PropertySpecificationLookup' ),
			$containerBuilder->singleton( 'EntityCache' )
		);

		return $unitConverter;
	},

	/**
	 * ConstraintSchemaValue
	 *
	 * @return callable
	 */
	ConstraintSchemaValue::class => function( $containerBuilder ) {
		return new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			$containerBuilder->singleton( 'PropertySpecificationLookup' )
		);
	},

	/**
	 * PropertyValueParser
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_PARSER . PropertyValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . PropertyValue::TYPE_ID,
			PropertyValueParser::class
		);

		$propertyValueParser = new PropertyValueParser();

		$propertyValueParser->setInvalidCharacterList(
			$containerBuilder->singleton( 'Settings' )->get( 'smwgPropertyInvalidCharacterList' )
		);

		$propertyValueParser->isCapitalLinks(
			Site::isCapitalLinks()
		);

		return $propertyValueParser;
	},

	/**
	 * PropertyValueFormatter
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_FORMATTER . PropertyValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . PropertyValue::TYPE_ID,
			PropertyValueFormatter::class
		);

		return new PropertyValueFormatter( $containerBuilder->singleton( 'PropertySpecificationLookup' ) );
	},

	/**
	 * AllowsPatternValueParser
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID,
			AllowsPatternValueParser::class
		);

		return new AllowsPatternValueParser( $containerBuilder->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * AllowsListValueParser
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID,
			AllowsListValueParser::class
		);

		return new AllowsListValueParser( $containerBuilder->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * CompoundConstraintValueValidator
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator' => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator',
			CompoundConstraintValueValidator::class
		);

		$propertySpecificationLookup = $containerBuilder->singleton( 'PropertySpecificationLookup' );
		$store = $containerBuilder->singleton( 'Store' );
		$constraintFactory = $containerBuilder->singleton( 'ConstraintFactory' );

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
			$containerBuilder->create( DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID )
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			$patternConstraintValueValidator
		);

		$allowsListConstraintValueValidator = new AllowsListConstraintValueValidator(
			$containerBuilder->create( DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID ),
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
			$containerBuilder->singleton( 'SchemaFactory' )->newSchemaFinder( $store )
		);

		$constraintSchemaValueValidator->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			$constraintSchemaValueValidator
		);

		$compoundConstraintValueValidator->setLogger(
			$containerBuilder->singleton( 'MediaWikiLogger' )
		);

		return $compoundConstraintValueValidator;
	},

	/**
	 * ImportValueParser
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_PARSER . ImportValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . ImportValue::TYPE_ID,
			ImportValueParser::class
		);

		return new ImportValueParser( $containerBuilder->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * StringValueFormatter
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_ID,
			StringValueFormatter::class
		);

		$containerBuilder->registerAlias(
			DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_ID,
			DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_LEGACY_ID
		);

		return new StringValueFormatter();
	},

	/**
	 * CodeStringValueFormatter
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_COD_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_COD_ID,
			CodeStringValueFormatter::class
		);

		return new CodeStringValueFormatter();
	},

	/**
	 * ReferenceValueFormatter
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_FORMATTER . ReferenceValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . ReferenceValue::TYPE_ID,
			ReferenceValueFormatter::class
		);

		return new ReferenceValueFormatter();
	},

	/**
	 * MonolingualTextValueParser
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_PARSER . MonolingualTextValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . MonolingualTextValue::TYPE_ID,
			MonolingualTextValueParser::class
		);

		return new MonolingualTextValueParser();
	},

	/**
	 * MonolingualTextValueFormatter
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_FORMATTER . MonolingualTextValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . MonolingualTextValue::TYPE_ID,
			MonolingualTextValueFormatter::class
		);

		return new MonolingualTextValueFormatter();
	},

	/**
	 * NumberValueFormatter
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_FORMATTER . QuantityValue::TYPE_ID => function( $containerBuilder ) {
		return $containerBuilder->create( DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID );
	},

	DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID,
			NumberValueFormatter::class
		);

		return new NumberValueFormatter();
	},

	/**
	 * TimeValueFormatter
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_FORMATTER . TimeValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . TimeValue::TYPE_ID,
			TimeValueFormatter::class
		);

		return new TimeValueFormatter();
	},

	/**
	 * TimeValueParser
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_PARSER . TimeValue::TYPE_ID => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . TimeValue::TYPE_ID,
			TimeValueParser::class
		);

		return new TimeValueParser();
	},

	/**
	 * TimeValueParser
	 *
	 * @return callable
	 */
	'DescriptionBuilderRegistry' => function( $containerBuilder ) {
		return new DescriptionBuilderRegistry();
	},

];
