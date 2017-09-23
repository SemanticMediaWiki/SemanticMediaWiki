<?php

namespace SMW\ParserFunctions;

use SMW\ParserData;
use Parser;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDIContainer as DIContainer;
use SMW\DataModel\MandatoryRequirements;

/**
 * Define individual mandatory requirements {{#mandatory: ... }} per entity.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class MandatoryParserFunction {

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @since 3.0
	 *
	 * @param ParserData $parserData
	 */
	public function __construct( ParserData $parserData ) {
		$this->parserData = $parserData;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return string|null
	 */
	public function parse( array $parameters ) {

		// Remove parser object from parameters array
		if( isset( $parameters[0] ) && $parameters[0] instanceof Parser ) {
			array_shift( $parameters );
		}

		$semanticData = $this->parserData->getSemanticData();
		$propertyList = [];

		foreach ( $parameters as $val ) {
			if ( strpos( $val, '=' ) !== false ) {
				list( $k, $v ) = explode( '=', $val, 2 );

				if ( $k === 'property' ) {
					$properties = explode( ',' , $v );
					foreach ( $properties as $prop ) {
						$prop = trim( $prop );
						$propertyList[$prop] = DIProperty::newFromUserLabel( $prop );
					}
				}
			}
		}

		$mandatoryRequirements = new MandatoryRequirements();

		$mandatoryRequirements->copyRequirements(
			$semanticData,
			$propertyList
		);

		$this->parserData->pushSemanticDataToParserOutput();
	}

}
