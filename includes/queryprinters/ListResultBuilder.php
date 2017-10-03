<?php

namespace SMW;

use SMWDataValue;
use SMWQueryResult;
use SMWResultArray;

/**
 * Class ListResultBuilder
 * @package SMWQuery
 */
class ListResultBuilder {


	private static $defaultConfigurations = [
		'list' => [
			'sep' => ', ',
			'propsep' => ', ',
			'valuesep' => ', ',
			'show-headers' => SMW_HEADERS_SHOW,
			'link-first' => true,
			'link-others' => true,
			'value-open-tag' => '<span class="list-format-value">',
			'value-close-tag' => '</span>',
			'field-open-tag' => '<span class="list-format-field">',
			'field-close-tag' => '</span>',
			'field-label-open-tag' => '<span class="list-format-field-label">',
			'field-label-close-tag' => '</span>',
			'field-label-separator' => ': ',
			'other-fields-open' => ' (',
			'other-fields-close' => ')',
			'row-open-tag' => '<span class="list-format-row">',
			'row-close-tag' => '</span>',
			'result-open-tag' => '<span class="list-format">',
			'result-close-tag' => '</span>',
		],
		'ul' => [
			'sep' => '',
			'propsep' => ', ',
			'valuesep' => ', ',
			'show-headers' => SMW_HEADERS_SHOW,
			'link-first' => true,
			'link-others' => true,
			'value-open-tag' => '<span class="ul-format-value">',
			'value-close-tag' => '</span>',
			'field-open-tag' => '<span class="ul-format-field">',
			'field-close-tag' => '</span>',
			'field-label-open-tag' => '<span class="ul-format-field-label">',
			'field-label-close-tag' => '</span>',
			'field-label-separator' => ': ',
			'other-fields-open' => ' (',
			'other-fields-close' => ')',
			'row-open-tag' => '<li class="ul-format-row">',
			'row-close-tag' => '</li>',
			'result-open-tag' => '<ul class="ul-format">',
			'result-close-tag' => '</ul>',
		],
	];

	private $linker = null;

	private $configuration = [];

	/**
	 * ListResultBuilder constructor.
	 * @param string $format
	 */
	public function __construct( $format = 'list' ) {

		if ( !array_key_exists( $format, self::$defaultConfigurations ) ) {
			$format = 'list';
		}

		$this->configuration = self::$defaultConfigurations[ $format ];
	}


	/**
	 * @param mixed $linker
	 */
	public function setLinker( $linker ) {
		$this->linker = $linker;
	}

	/**
	 * @param $setting
	 * @return mixed
	 */
	protected function get( $setting ) {
		return $this->configuration[ $setting ];
	}

	/**
	 * @param $setting
	 * @return mixed
	 */
	public function set( $setting, $value ) {
		if ( array_key_exists( $setting, $this->configuration ) ) {
			$this->configuration[ $setting ] = $value;
		}
	}

	/**
	 * @param SMWQueryResult $queryResult
	 * @return string
	 */
	public function getResultText( SMWQueryResult $queryResult ) {
		return
			$this->get( 'result-open-tag' ) .
			join( $this->get( 'sep' ), $this->getRowTexts( $queryResult ) ) .
			$this->get( 'result-close-tag' );
	}

	/**
	 * @param SMWQueryResult $queryResult
	 * @return string[]
	 */
	protected function getRowTexts( SMWQueryResult $queryResult ) {

		$rowTexts = [];

		while ( ( $row = $queryResult->getNext() ) !== false ) {
			$rowTexts[] = $this->getRowText( $row );
		}

		return $rowTexts;
	}

	/**
	 * @param SMWResultArray[] $fields
	 * @return string
	 */
	protected function getRowText( array $fields ) {

		$fieldTexts = $this->getFieldTexts( $fields );

		$firstFieldText = array_shift( $fieldTexts );

		if ( $firstFieldText === null ) {
			return '';
		}

		if ( count( $fieldTexts ) > 0 ) {
			$otherFieldsText = $this->get( 'other-fields-open' ) . join( $this->get( 'propsep' ), $fieldTexts ) . $this->get( 'other-fields-close' );
		} else {
			$otherFieldsText = '';
		}

		return
			$this->get( 'row-open-tag' ) .
			$firstFieldText .
			$otherFieldsText .
			$this->get( 'row-close-tag' );
	}

	/**
	 * @param string[] $fields
	 * @return array
	 */
	protected function getFieldTexts( array $fields ) {

		$columnNumber = 0;
		$fieldTexts = [];

		foreach ( $fields as $field ) {
			$fieldTexts[] = $this->getFieldText( $field, $columnNumber );
			$columnNumber++;
		}

		return $fieldTexts;
	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 * @return string
	 */
	protected function getFieldText( SMWResultArray $field, $column = 0 ) {

		$label = $this->getFieldLabel( $field );
		$valueTexts = $this->getValueTexts( $field, $column );

		return $label . join( $this->get( 'valuesep' ), $valueTexts );

	}

	/**
	 * @param SMWResultArray $field
	 * @return string
	 */
	protected function getFieldLabel( SMWResultArray $field ) {

		if ( $this->get( 'show-headers' ) !== SMW_HEADERS_HIDE && $field->getPrintRequest()->getLabel() !== '' ) {

			$linker = $this->get( 'show-headers' ) === SMW_HEADERS_PLAIN ? null : $this->linker;

			return
				$this->get( 'field-label-open-tag' ) .
				$field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, $linker ) .
				$this->get( 'field-label-close-tag' ) .
				$this->get( 'field-label-separator' );
		}

		return '';
	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 * @return string[]
	 */
	protected function getValueTexts( SMWResultArray $field, $column ) {
		$valueTexts = [];

		while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {
			$valueTexts[] = $this->getValueText( $dataValue, $column );
		}

		return $valueTexts;
	}

	/**
	 * @param SMWDataValue $value
	 * @param int $column
	 * @return string
	 */
	protected function getValueText( SMWDataValue $value, $column = 0 ) {
		return $this->get( 'value-open-tag' ) .
			$value->getShortText( SMW_OUTPUT_WIKI, $this->getLinkerForColumn( $column ) ) .
			$this->get( 'value-close-tag' );
	}

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param int $column Column number
	 * @return \Linker|null
	 */
	protected function getLinkerForColumn( $column ) {

		if ( ( $column === 0 && $this->get( 'link-first' ) ) || ( $column > 0 && $this->get( 'link-others' ) ) ) {
			return $this->linker;
		}

		return null;
	}

}