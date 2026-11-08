<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

use MediaWiki\Linker\Linker;
use MediaWiki\Parser\Sanitizer;
use SMW\DataValues\DataValue;
use SMW\DataValues\WikiPageValue;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\PrefixParameterProcessor;
use SMW\Query\ResultPrinters\SeparatorEscaper;

/**
 * Class ValueTextsBuilder
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class ValueTextsBuilder {

	use ParameterDictionaryUser;

	private ?Linker $linker = null;

	public function __construct( private PrefixParameterProcessor $prefixParameterProcessor ) {
	}

	/**
	 * @param ResultArray $field
	 * @param int $column
	 *
	 * @return string
	 */
	public function getValuesText( ResultArray $field, $column = 0 ): string {
		$valueTexts = $this->getValueTexts( $field, $column );

		return implode(
			SeparatorEscaper::escape( $this->get( 'valuesep' ), $this->get( 'output-mode', SMW_OUTPUT_HTML ) ),
			$valueTexts
		);
	}

	/**
	 * @param ResultArray $field
	 * @param int $column
	 *
	 * @return string[]
	 */
	private function getValueTexts( ResultArray $field, $column ): array {
		$valueTexts = [];

		$field->reset();
		$printRequest = $field->getPrintRequest();
		$dataValue = $field->getNextDataValue();
		while ( $dataValue !== false ) {
			$valueTexts[] =
				$this->get( 'value-open-tag' ) .
				$this->getValueText( $dataValue, $column, $printRequest?->getMode() ) .
				$this->get( 'value-close-tag' );
			$dataValue = $field->getNextDataValue();
		}

		return $valueTexts;
	}

	/**
	 * @param DataValue $value
	 * @param int $column
	 *
	 * @return string
	 */
/*
	private function getValueText( DataValue $value, $column = 0 ): string {
		$isSubject = ( $column === 0 );
		$dataValueMethod = $this->prefixParameterProcessor->useLongText( $isSubject ) ? 'getLongText' : 'getShortText';

		$text = $value->$dataValueMethod( SMW_OUTPUT_WIKI, $this->getLinkerForColumn( $column ) );

		return $this->sanitizeValueText( $text );
	}
*/
	private function getValueText( DataValue $dataValue, $column = 0, ?int $printRequestMode = null ): string {
		$isSubject = ( $column === 0 );
		$linker = $this->getLinkerForColumn( $column );

		// Fall back to PRINT_PROP when no mode was supplied (e.g. unstubbed
		// PrintRequest mocks in unit tests, or callers that omit the mode).
		$printRequestMode ??= PrintRequest::PRINT_PROP;

		// @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6305
		$useLongText = $this->prefixParameterProcessor->useLongText( $printRequestMode, $dataValue );
		$dataValueMethod = $useLongText ? 'getLongText' : 'getShortText';

		if ( $dataValue instanceof WikiPageValue ) {
			$dataValue->setOption(
				$useLongText
					? $dataValue::PREFIXED_FORM
					: $dataValue::SHORT_FORM,
				true
			);
		}

		$text = $dataValue->$dataValueMethod( SMW_OUTPUT_WIKI, $linker );
		return $this->sanitizeValueText( $text );
	}

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param int $columnNumber Column number
	 *
	 * @return Linker|null
	 */
	private function getLinkerForColumn( $columnNumber ): ?Linker {
		if ( ( $columnNumber === 0 && $this->get( 'link-first' ) ) ||
			( $columnNumber > 0 && $this->get( 'link-others' ) ) ) {
			return $this->getLinker();
		}

		return null;
	}

	/**
	 * @return Linker|null
	 */
	protected function getLinker(): ?Linker {
		return $this->linker;
	}

	/**
	 * @param Linker $linker
	 */
	public function setLinker( Linker $linker ): void {
		$this->linker = $linker;
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	private function sanitizeValueText( string $text ): string {
		if ( $this->isSimpleList() ) {
			return $text;
		}

		return Sanitizer::removeSomeTags(
			$text, [ 'removeTags' => [ 'table', 'tr', 'th', 'td', 'dl', 'dd', 'ul', 'li', 'ol' ] ]
		);
	}

	/**
	 * @return bool
	 */
	private function isSimpleList(): bool {
		$format = $this->get( 'format' );
		return $format !== 'ul' && $format !== 'ol';
	}

}
