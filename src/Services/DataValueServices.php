<?php

namespace SMW\Services;

use SMW\DataValues\ValueParsers\PropertyValueParser;
use SMW\DataValues\ValueFormatters\PropertyValueFormatter;
use SMWPropertyValue as PropertyValue;

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

);