<?php

namespace SMW\Services;

use Onoi\CallbackContainer\CallbackContainerBuilder;
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
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
return [

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return UnitConverter
	 */
	'UnitConverter' => static function ( $containerBuilder ): UnitConverter {
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
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return ConstraintSchemaValue
	 */
	ConstraintSchemaValue::class => static function ( $containerBuilder ): ConstraintSchemaValue {
		return new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			$containerBuilder->singleton( 'PropertySpecificationLookup' )
		);
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return PropertyValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . PropertyValue::TYPE_ID => static function ( $containerBuilder ): PropertyValueParser {
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
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return PropertyValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . PropertyValue::TYPE_ID => static function ( $containerBuilder ): PropertyValueFormatter {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . PropertyValue::TYPE_ID,
			PropertyValueFormatter::class
		);

		return new PropertyValueFormatter( $containerBuilder->singleton( 'PropertySpecificationLookup' ) );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return AllowsPatternValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID => static function ( $containerBuilder ): AllowsPatternValueParser {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID,
			AllowsPatternValueParser::class
		);

		return new AllowsPatternValueParser( $containerBuilder->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return AllowsListValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID => static function ( $containerBuilder ): AllowsListValueParser {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . AllowsListValue::TYPE_ID,
			AllowsListValueParser::class
		);

		return new AllowsListValueParser( $containerBuilder->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return CompoundConstraintValueValidator
	 */
	DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator' => static function ( $containerBuilder ): CompoundConstraintValueValidator {
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
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return ImportValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . ImportValue::TYPE_ID => static function ( $containerBuilder ): ImportValueParser {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . ImportValue::TYPE_ID,
			ImportValueParser::class
		);

		return new ImportValueParser( $containerBuilder->singleton( 'MediaWikiNsContentReader' ) );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return StringValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_ID => static function ( $containerBuilder ): StringValueFormatter {
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
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return CodeStringValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_COD_ID => static function ( $containerBuilder ): CodeStringValueFormatter {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . StringValue::TYPE_COD_ID,
			CodeStringValueFormatter::class
		);

		return new CodeStringValueFormatter();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return ReferenceValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . ReferenceValue::TYPE_ID => static function ( $containerBuilder ): ReferenceValueFormatter {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . ReferenceValue::TYPE_ID,
			ReferenceValueFormatter::class
		);

		return new ReferenceValueFormatter();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return MonolingualTextValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . MonolingualTextValue::TYPE_ID => static function ( $containerBuilder ): MonolingualTextValueParser {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . MonolingualTextValue::TYPE_ID,
			MonolingualTextValueParser::class
		);

		return new MonolingualTextValueParser();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return MonolingualTextValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . MonolingualTextValue::TYPE_ID => static function ( $containerBuilder ): MonolingualTextValueFormatter {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . MonolingualTextValue::TYPE_ID,
			MonolingualTextValueFormatter::class
		);

		return new MonolingualTextValueFormatter();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return NumberValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . QuantityValue::TYPE_ID => static function ( $containerBuilder ) {
		return $containerBuilder->create( DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID );
	},

	DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID => static function ( $containerBuilder ): NumberValueFormatter {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . NumberValue::TYPE_ID,
			NumberValueFormatter::class
		);

		return new NumberValueFormatter();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return TimeValueFormatter
	 */
	DataValueServiceFactory::TYPE_FORMATTER . TimeValue::TYPE_ID => static function ( $containerBuilder ): TimeValueFormatter {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_FORMATTER . TimeValue::TYPE_ID,
			TimeValueFormatter::class
		);

		return new TimeValueFormatter();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return TimeValueParser
	 */
	DataValueServiceFactory::TYPE_PARSER . TimeValue::TYPE_ID => static function ( $containerBuilder ): TimeValueParser {
		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_PARSER . TimeValue::TYPE_ID,
			TimeValueParser::class
		);

		return new TimeValueParser();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return DescriptionBuilderRegistry
	 */
	'DescriptionBuilderRegistry' => static function ( $containerBuilder ): DescriptionBuilderRegistry {
		return new DescriptionBuilderRegistry();
	},

];
