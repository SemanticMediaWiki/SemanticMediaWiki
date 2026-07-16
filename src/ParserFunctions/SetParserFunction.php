<?php

namespace SMW\ParserFunctions;

use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\Formatters\MessageFormatter;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use SMW\MediaWiki\StripMarkerDecoder;
use SMW\Parser\AnnotationProcessor;
use SMW\ParserData;
use SMW\ParserParameterProcessor;

/**
 * Class that provides the {{#set}} parser function
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Properties_and_types#Silent_annotations_using_.23set
 * @see http://www.semantic-mediawiki.org/wiki/Help:Setting_values
 *
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class SetParserFunction {

	private const DISPLAY_LINK = 'link';
	private const DISPLAY_TEXT = 'text';

	private ?StripMarkerDecoder $stripMarkerDecoder = null;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private readonly ParserData $parserData,
		private readonly MessageFormatter $messageFormatter,
		private readonly WikitextTemplateRenderer $templateRenderer,
	) {
	}

	/**
	 * @since 3.0
	 *
	 * @param StripMarkerDecoder $stripMarkerDecoder
	 */
	public function setStripMarkerDecoder( StripMarkerDecoder $stripMarkerDecoder ): void {
		$this->stripMarkerDecoder = $stripMarkerDecoder;
	}

	/**
	 * @since 3.1
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->parserData->getSemanticData();
	}

	/**
	 * @since  1.9
	 */
	public function parse( ParserParameterProcessor $parameters ): array {
		$count = 0;
		$template = '';
		$subject = $this->parserData->getSemanticData()->getSubject();

		$parametersToArray = $parameters->toArray();

		if ( isset( $parametersToArray['template'] ) ) {
			$template = $parametersToArray['template'][0];
			unset( $parametersToArray['template'] );
		}

		$displayModes = $this->determineDisplayModes(
			$parameters->getDisplayOptions(),
			$template
		);

		$annotationProcessor = new AnnotationProcessor(
			$this->parserData->getSemanticData(),
			DataValueFactory::getInstance()
		);

		$displayParts = [];

		foreach ( $parametersToArray as $property => $values ) {

			$last = count( $values ) - 1; // -1 because the key starts with 0

			foreach ( $values as $key => $value ) {

				$origValue = $value;

				if ( $this->stripMarkerDecoder !== null ) {
					$value = $this->stripMarkerDecoder->decode( $value );
				}

				$dataValue = $annotationProcessor->newDataValueByText(
						$property,
						$value,
						false,
						$subject
					);

				if ( $this->parserData->canUse() ) {
					$this->parserData->addDataValue( $dataValue );
				}

				$this->messageFormatter->addFromArray( $dataValue->getErrors() );

				if ( isset( $displayModes[$property] ) ) {
					$displayPart = $this->renderDisplayValue( $displayModes[$property], $dataValue, $origValue, $value );

					if ( $displayPart !== null ) {
						$displayParts[] = $displayPart;
					}
				}

				$this->addFieldsToTemplate(
					$template,
					$dataValue,
					$property,
					$value,
					$last == $key,
					$count
				);
			}
		}

		if ( $this->parserData->variesByUserLanguage() ) {
			// Bridge the flag recorded during rendering into the parser cache
			// key, as the inline annotation path does; without this the value
			// would be cached regardless of the viewer's interface language
			$this->parserData->addExtraParserKey( 'userlang' );
		}

		$this->parserData->copyToParserOutput();

		$annotationProcessor->release();

		$displayText = implode( ', ', $displayParts );

		$errorHtml = $this->messageFormatter
			->addFromArray( $parameters->getErrors() )
			->getHtml();

		if ( $displayText !== '' ) {
			// Encode `:` so the error output cannot form annotations or comment
			// blocks when the result is parsed, as the inline annotation path does
			$errorHtml = str_replace( ':', '&#58;', $errorHtml );
		}

		$html = $this->templateRenderer->render() . $displayText . $errorHtml;

		return [ $html, 'noparse' => $template === '' && $displayText === '', 'isHTML' => false ];
	}

	/**
	 * @param array<string, string> $displayOptions
	 *
	 * @return array<string, string> property name => self::DISPLAY_LINK|self::DISPLAY_TEXT
	 */
	private function determineDisplayModes( array $displayOptions, string $template ): array {
		if ( $displayOptions === [] ) {
			return [];
		}

		if ( $template !== '' ) {
			$this->messageFormatter->addFromKey( 'smw-parser-function-set-display-template-conflict' );
			return [];
		}

		$modes = [];

		foreach ( $displayOptions as $property => $option ) {
			$mode = $option === '' ? self::DISPLAY_LINK : strtolower( $option );

			if ( $mode === self::DISPLAY_LINK || $mode === self::DISPLAY_TEXT ) {
				$modes[$property] = $mode;
			} else {
				$this->messageFormatter->addFromKey( 'smw-parser-function-set-display-invalid-mode', $option );
			}
		}

		return $modes;
	}

	private function renderDisplayValue( string $mode, DataValue $dataValue, string $origValue, $decodedValue ): ?string {
		if ( !$dataValue->isValid() ) {
			// An invalid value renders a localized error, so the output is not
			// cache-stable across languages, as the inline annotation path does
			$this->parserData->markVariesByUserLanguage();
			return null;
		}

		// A changed value signals a strip marker; return the raw original so
		// the standard Parser restores the stripped content, as the inline
		// annotation path does
		if ( $origValue !== $decodedValue ) {
			return $origValue;
		}

		if ( $mode === self::DISPLAY_TEXT ) {
			$wikiText = $dataValue->getShortWikiText();
		} else {
			$wikiText = $dataValue->getShortWikiText( true );
			$this->addFileUsage( $dataValue );
		}

		// getShortWikiText() records user-language output while formatting (e.g.
		// a unit-conversion tooltip), so this must be checked after rendering,
		// mirroring the inline annotation path
		if ( $dataValue->hasUserLanguageOutput() ) {
			$this->parserData->markVariesByUserLanguage();
		}

		return $wikiText;
	}

	/**
	 * Records a file referenced through a displayed property value as a
	 * dependency of the parsed page, mirroring the inline annotation path
	 * (#6141): an embedded image registers this link through its `[[File:...]]`
	 * markup, but a non-image file is rendered as a plain link whose dependency
	 * would otherwise be missing.
	 */
	private function addFileUsage( DataValue $dataValue ): void {
		$dataItem = $dataValue->getDataItem();

		if (
			$dataItem instanceof WikiPage &&
			$dataItem->getNamespace() === NS_FILE &&
			$dataItem->getInterwiki() === '' &&
			$dataItem->getSubobjectName() === ''
		) {
			$this->parserData->getOutput()->addImage( $dataItem->getDBkey() );
		}
	}

	private function addFieldsToTemplate( $template, $dataValue, $property, $value, bool $isLastElement, &$count ): string {
		if ( $template === '' || !$dataValue->isValid() ) {
			return '';
		}

		$this->templateRenderer->addField( 'property', $property );
		$this->templateRenderer->addField( 'value', $value );
		$this->templateRenderer->addField( 'last-element', $isLastElement );
		$this->templateRenderer->addField( '#', $count++ );
		$this->templateRenderer->packFieldsForTemplate( $template );

		return '';
	}

}
