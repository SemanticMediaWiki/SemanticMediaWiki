<?php

namespace SMW;

use SMWPropertyValue as PropertyValue;

use Parser;
use PPFrame;

/**
 * Class that provides the {{#declare}} parser function
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Argument_declaration_in_templates
 *
 * @license GNU GPL v2+
 * @since   1.5.3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class DeclareParserFunction {

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @since 2.1
	 *
	 * @param ParserData $parserData
	 */
	public function __construct( ParserData $parserData ) {
		$this->parserData = $parserData;
	}

	/**
	 * @since 2.1
	 *
	 * @param PPFrame $frame
	 * @param array $args
	 */
	public function parse( PPFrame $frame, array $args ) {

		// @todo Save as metadata
		if ( !$frame->isTemplate() ) {
			return '';
		}

		$this->subject = $this->parserData->getSemanticData()->getSubject();

		foreach ( $args as $arg ) {
			if ( trim( $arg ) !== '' ) {
				$expanded = trim( $frame->expand( $arg ) );
				$parts = explode( '=', $expanded, 2 );

				if ( count( $parts ) == 1 ) {
					$propertystring = $expanded;
					$argumentname = $expanded;
				} else {
					$propertystring = $parts[0];
					$argumentname = $parts[1];
				}

				$propertyValue = PropertyValue::makeUserProperty( $propertystring );
				$argument = $frame->getArgument( $argumentname );
				$valuestring = $frame->expand( $argument );

				if ( $propertyValue->isValid() ) {
					$this->matchValueArgument( $propertyValue, $propertystring, $valuestring );
				}
			}
		}

		$this->parserData->pushSemanticDataToParserOutput();

		return '';
	}

	private function matchValueArgument( PropertyValue $propertyValue, $propertystring, $valuestring ) {

		if ( $propertyValue->getPropertyTypeID() === '_wpg' ) {
			$matches = array();
			preg_match_all( '/\[\[([^\[\]]*)\]\]/u', $valuestring, $matches );
			$objects = $matches[1];

			if ( count( $objects ) == 0 ) {
				if ( trim( $valuestring ) !== '' ) {
					$this->addDataValue( $propertystring, $valuestring );
				}
			} else {
				foreach ( $objects as $object ) {
					$this->addDataValue( $propertystring, $object );
				}
			}
		} elseif ( trim( $valuestring ) !== '' ) {
			$this->addDataValue( $propertystring, $valuestring );
		}

		// $value = \SMW\DataValueFactory::getInstance()->newPropertyObjectValue( $property->getDataItem(), $valuestring );
		// if (!$value->isValid()) continue;
	}

	private function addDataValue( $property, $value ) {

		$dataValue = DataValueFactory::getInstance()->newPropertyValue(
			$property,
			$value,
			false,
			$this->subject
		);

		$this->parserData->addDataValue( $dataValue );
	}

}
