<?php

namespace SMW\Services;

use SMW\DataValues\ImportValue;
use SMW\DataValues\ValueParsers\ImportValueParser;
use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMW\DataValues\ValueParsers\AllowsPatternValueParser;
use SMW\DataValues\AllowsPatternValue;
use SMWPropertyValue as PropertyValue;
use SMW\DataValues\ValueValidators\CompoundConstraintValueValidator;
use SMW\DataValues\ValueValidators\UniquenessConstraintValueValidator;
use SMW\DataValues\ValueValidators\PatternConstraintValueValidator;
use SMW\DataValues\ValueValidators\ListConstraintValueValidator;
use SMW\DataValues\ValueValidators\PropertySpecificationConstraintValueValidator;

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
return array(

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

		return new PropertyValueFormatter();
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
	 * CompoundConstraintValueValidator
	 *
	 * @return callable
	 */
	DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator' => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType(
			DataValueServiceFactory::TYPE_VALIDATOR . 'CompoundConstraintValueValidator',
			CompoundConstraintValueValidator::class
		);

		$compoundConstraintValueValidator = new CompoundConstraintValueValidator();

		// Any registered ConstraintValueValidator becomes weaker(diminished) in the context
		// of a preceding validator
		$compoundConstraintValueValidator->registerConstraintValueValidator(
			new UniquenessConstraintValueValidator()
		);

		$patternConstraintValueValidator = new PatternConstraintValueValidator(
			$containerBuilder->create( DataValueServiceFactory::TYPE_PARSER . AllowsPatternValue::TYPE_ID )
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			$patternConstraintValueValidator
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			new ListConstraintValueValidator()
		);

		$compoundConstraintValueValidator->registerConstraintValueValidator(
			new PropertySpecificationConstraintValueValidator()
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

);
