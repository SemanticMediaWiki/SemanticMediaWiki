<?php

namespace SMW;

use SMW\MediaWiki\Renderer\HtmlTemplateRenderer;

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
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var MessageFormatter
	 */
	private $messageFormatter;

	/**
	 * @var HtmlTemplateRenderer
	 */
	private $templateRenderer;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MessageFormatter $messageFormatter
	 * @param HtmlTemplateRenderer $templateRenderer
	 */
	public function __construct( ParserData $parserData, MessageFormatter $messageFormatter, HtmlTemplateRenderer $templateRenderer ) {
		$this->parserData = $parserData;
		$this->messageFormatter = $messageFormatter;
		$this->templateRenderer = $templateRenderer;
	}

	/**
	 * @since  1.9
	 *
	 * @param ArrayFormatter $parameters
	 *
	 * @return string|null
	 */
	public function parse( ArrayFormatter $parameters ) {

		$count = 0;
		$template = '';
		$subject = $this->parserData->getSemanticData()->getSubject();

		$parametersToArray = $parameters->toArray();

		if ( isset( $parametersToArray['template'] ) ) {
			$template = $parametersToArray['template'][0];
			unset( $parametersToArray['template'] );
		}

		foreach ( $parametersToArray as $property => $values ) {

			foreach ( $values as $value ) {

				$dataValue = DataValueFactory::getInstance()->newPropertyValue(
						$property,
						$value,
						false,
						$subject
					);

				$this->parserData->addDataValue(
					$dataValue
				);

				$this->messageFormatter->addFromArray( $dataValue->getErrors() );

				$this->addFieldsToTemplate(
					$template,
					$dataValue,
					$property,
					$value,
					$count
				);
			}
		}

		$this->parserData->pushSemanticDataToParserOutput();

		$html = $this->templateRenderer->render() . $this->messageFormatter
			->addFromArray( $parameters->getErrors() )
			->getHtml();

		return array( $html, 'noparse' => true, 'isHTML' => false );
	}

	private function addFieldsToTemplate( $template, $dataValue, $property, $value, &$count ) {

		if ( $template === '' || !$dataValue->isValid() ) {
			return '';
		}

		$this->templateRenderer->addField( 'property', $property );
		$this->templateRenderer->addField( 'value', $value );
		$this->templateRenderer->addField( '#', $count++ );
		$this->templateRenderer->packFieldsForTemplate( $template );
	}

}
