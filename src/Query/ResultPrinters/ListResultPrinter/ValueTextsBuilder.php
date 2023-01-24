<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

use Linker;
use Sanitizer;
use SMW\Query\Language\NamespaceDescription;
use SMWDataValue;
use SMWQueryResult as QueryResult;
use SMWResultArray;

/**
 * Class ValueTextsBuilder
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class ValueTextsBuilder {

	use ParameterDictionaryUser;

	private $linker;

	private $mixedResults;

	/**
	 * @param SMWQueryResult $queryResult
	 */
	public function __construct( QueryResult $queryResult ) {
		$this->mixedResults = !( $queryResult->getQuery()->getDescription() instanceof NamespaceDescription );
	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 *
	 * @return string
	 */
	public function getValuesText( SMWResultArray $field, $column = 0 ) {

		$valueTexts = $this->getValueTexts( $field, $column );

		return join( $this->get( 'valuesep' ), $valueTexts );

	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 *
	 * @return string[]
	 */
	private function getValueTexts( SMWResultArray $field, $column ) {

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
		$text = $this->getDataValueText( $value, SMW_OUTPUT_WIKI, $this->getLinkerForColumn( $column ) );

		return $this->sanitizeValueText( $text );
	}

	/**
	 * @param @param SMWDataValue $dv
	 * @param int $outputMode
	 * @param Linker|null|bool $linker
	 * @return string
	 */
	protected function getDataValueText( $dv, $outputMode, $linker ) {
		return $this->mixedResults ? $dv->getLongText( $outputMode, $linker ) : 
			$dv->getShortText( $outputMode, $linker );
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

		if ( method_exists( Sanitizer::class, 'removeSomeTags' ) ) {
			return Sanitizer::removeSomeTags(
				$text, [ 'removeTags' => [ 'table', 'tr', 'th', 'td', 'dl', 'dd', 'ul', 'li', 'ol' ] ]
			);
		} else {
			return Sanitizer::removeHTMLtags(
				$text, null, [], [], [ 'table', 'tr', 'th', 'td', 'dl', 'dd', 'ul', 'li', 'ol' ]
			);
		}
	}

	/**
	 * @return bool
	 */
	private function isSimpleList() {
		$format = $this->get( 'format' );
		return $format !== 'ul' && $format !== 'ol';
	}

}
