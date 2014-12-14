<?php

namespace SMW;

use Parser;

/**
 * Class that provides the {{#set}} parser function
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Properties_and_types#Silent_annotations_using_.23set
 * @see http://www.semantic-mediawiki.org/wiki/Help:Setting_values
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class SetParserFunction {

	/**
	 * @var ParserDate
	 */
	private $parserData;

	/**
	 * @var MessageFormatter
	 */
	private $messageFormatter;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MessageFormatter $messageFormatter
	 */
	public function __construct( ParserData $parserData, MessageFormatter $messageFormatter ) {
		$this->parserData = $parserData;
		$this->messageFormatter = $messageFormatter;
	}

	/**
	 * @since  1.9
	 *
	 * @param ArrayFormatter $parameters
	 *
	 * @return string|null
	 */
	public function parse( ArrayFormatter $parameters ) {

		$subject = $this->parserData->getSemanticData()->getSubject();

		foreach ( $parameters->toArray() as $property => $values ){
			foreach ( $values as $value ) {

				$dataValue = DataValueFactory::getInstance()->newPropertyValue(
						$property,
						$value,
						false,
						$subject
					);

				$this->parserData->addDataValue( $dataValue );
			}
		}

		$this->parserData->pushSemanticDataToParserOutput();

		return $this->messageFormatter
			->addFromArray( $this->parserData->getErrors() )
			->addFromArray( $parameters->getErrors() )
			->getHtml();
	}

}
