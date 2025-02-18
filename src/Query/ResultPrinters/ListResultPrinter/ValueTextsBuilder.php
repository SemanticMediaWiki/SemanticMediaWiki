<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

use Linker;
use Sanitizer;
use SMW\Query\Result\ResultArray;
use SMW\Query\ResultPrinters\PrefixParameterProcessor;
use SMWDataValue;

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

	private $linker;
	private $prefixParameterProcessor;

	public function __construct( PrefixParameterProcessor $prefixParameterProcessor ) {
		$this->prefixParameterProcessor = $prefixParameterProcessor;
	}

	/**
	 * @param ResultArray $field
	 * @param int $column
	 *
	 * @return string
	 */
	public function getValuesText( ResultArray $field, $column = 0 ) {
		$valueTexts = $this->getValueTexts( $field, $column );

		return implode( $this->get( 'valuesep' ), $valueTexts );
	}

	/**
	 * @param ResultArray $field
	 * @param int $column
	 *
	 * @return string[]
	 */
	private function getValueTexts( ResultArray $field, $column ) {
		$valueTexts = [];

		$field->reset();

		while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {

			$valueTexts[] =
				$this->get( 'value-open-tag' ) .
				$this->getValueText( $dataValue, $column ) .
				$this->get( 'value-close-tag' );
		}

		return $valueTexts;
	}

	/**
	 * @param SMWDataValue $value
	 * @param int $column
	 *
	 * @return string
	 */
	private function getValueText( SMWDataValue $value, $column = 0 ) {
		$isSubject = ( $column === 0 );
		$dataValueMethod = $this->prefixParameterProcessor->useLongText( $isSubject ) ? 'getLongText' : 'getShortText';

		$text = $value->$dataValueMethod( SMW_OUTPUT_WIKI, $this->getLinkerForColumn( $column ) );

		return $this->sanitizeValueText( $text );
	}

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param int $columnNumber Column number
	 *
	 * @return \Linker|null
	 */
	private function getLinkerForColumn( $columnNumber ) {
		if ( ( $columnNumber === 0 && $this->get( 'link-first' ) ) ||
			( $columnNumber > 0 && $this->get( 'link-others' ) ) ) {
			return $this->getLinker();
		}

		return null;
	}

	/**
	 * @return Linker
	 */
	protected function getLinker() {
		return $this->linker;
	}

	/**
	 * @param Linker $linker
	 */
	public function setLinker( Linker $linker ) {
		$this->linker = $linker;
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	private function sanitizeValueText( $text ) {
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
	private function isSimpleList() {
		$format = $this->get( 'format' );
		return $format !== 'ul' && $format !== 'ol';
	}

}
