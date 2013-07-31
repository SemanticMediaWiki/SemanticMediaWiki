<?php

namespace SMW;

use Parser;

/**
 * Class that provides the {{#set}} parser function
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Properties_and_types#Silent_annotations_using_.23set
 * @see http://www.semantic-mediawiki.org/wiki/Help:Setting_values
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#set}} parser function
 *
 * @ingroup ParserFunction
 */
class SetParserFunction {

	/** @var IParserDate */
	protected $parserData;

	/** @var MessageFormatter */
	protected $msgFormatter;

	/**
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 * @param MessageFormatter $msgFormatter
	 */
	public function __construct( IParserData $parserData, MessageFormatter $msgFormatter ) {
		$this->parserData = $parserData;
		$this->msgFormatter = $msgFormatter;
	}

	/**
	 * Parse parameters and store its results to the ParserOutput object
	 *
	 * @since  1.9
	 *
	 * @param ArrayFormatter $parameters
	 *
	 * @return string|null
	 */
	public function parse( ArrayFormatter $parameters ) {

		// Add dataValues
		foreach ( $parameters->toArray() as $property => $values ){
			foreach ( $values as $value ) {
				$this->parserData->addPropertyValue(
					DataValueFactory::newPropertyValue(
						$property,
						$value
					)
				);
			}
		}

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->msgFormatter->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

	/**
	 * Parser::setFunctionHook {{#set}} handler method
	 *
	 * @param Parser $parser
	 *
	 * @return string|null
	 */
	public static function render( Parser &$parser ) {
		$set = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new MessageFormatter( $parser->getTargetLanguage() )
		);
		return $set->parse( ParameterFormatterFactory::newFromArray( func_get_args() ) );
	}
}
