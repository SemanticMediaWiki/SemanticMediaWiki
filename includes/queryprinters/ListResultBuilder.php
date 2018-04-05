<?php

namespace SMW;

use Linker;
use SMW\MediaWiki\Renderer\WikitextTemplateRenderer;
use SMWDataValue;
use SMWQueryResult;
use SMWResultArray;

/**
 * Class ListResultBuilder
 * @package SMWQuery
 */
class ListResultBuilder {

	private static $defaultConfigurations = [
		'*' => [
			'sep' => '',
			'propsep' => ', ',
			'valuesep' => ', ',
			'show-headers' => SMW_HEADERS_SHOW,
			'link-first' => true,
			'link-others' => true,
			'template' => '',
			'introtemplate' => '',
			'outrotemplate' => '',

			'value-open-tag' => '<div class="smw-value">',
			'value-close-tag' => '</div>',
			'field-open-tag' => '<div class="smw-field">',
			'field-close-tag' => '</div>',
			'field-label-open-tag' => '<div class="smw-field-label">',
			'field-label-close-tag' => '</div>',
			'field-label-separator' => ': ',
			'other-fields-open' => ' (',
			'other-fields-close' => ')',
			'row-open-tag' => '<div class="smw-row">',
			'row-close-tag' => '</div>',
			'result-open-tag' => '<div class="smw-format list-format">',
			'result-close-tag' => '</div>',
		],
		'list' => [
			'row-open-tag' => '<div class="smw-row">',
			'row-close-tag' => '</div>',
			'result-open-tag' => '<div class="smw-format list-format">',
			'result-close-tag' => '</div>',
		],
		'ol' => [
			'row-open-tag' => '<li class="smw-row">',
			'row-close-tag' => '</li>',
			'result-open-tag' => '<ol class="smw-format ol-format">',
			'result-close-tag' => '</ol>',
		],
		'ul' => [
			'row-open-tag' => '<li class="smw-row">',
			'row-close-tag' => '</li>',
			'result-open-tag' => '<ul class="smw-format ul-format">',
			'result-close-tag' => '</ul>',
		],
		'template' => [
			'row-open-tag' => '',
			'row-close-tag' => '',
			'result-open-tag' => '<div class="smw-format list-format">',
			'result-close-tag' => '</div>',
		],
	];

	private $linker = null;

	private $configuration = [];

	/**
	 * @var SMWQueryResult
	 */
	private $queryResult;

	private $numberOfPages = null;
	private $templateRenderer;

	/**
	 * ListResultBuilder constructor.
	 *
	 * @param SMWQueryResult $queryResult
	 * @param Linker $linker
	 */
	public function __construct( SMWQueryResult $queryResult, Linker $linker ) {
		$this->queryResult = $queryResult;
		$this->linker = $linker;
	}

	/**
	 * @param string|string[] $setting
	 * @param string|null $value
	 */
	public function set( $setting, $value = null ) {

		if ( !is_array( $setting ) ) {
			$setting = [ $setting => $value ];
		}

		$this->configuration = array_replace( $this->configuration, $setting );
	}

	/**
	 * @param string $setting
	 * @param string $default
	 *
	 * @return mixed
	 */
	protected function get( $setting, $default = '' ) {
		return isset( $this->configuration[ $setting ] ) ? $this->configuration[ $setting ] : $default;
	}

	/**
	 * @return SMWQueryResult
	 */
	public function getQueryResult() {
		return $this->queryResult;
	}

	/**
	 * @return bool
	 */
	public function hasTemplates() {
		return $this->get( 'template' ) !== '' || $this->get( 'introtemplate' ) !== '' || $this->get( 'outrotemplate' ) !== '';
	}

	/**
	 * @return string
	 */
	public function getResultText() {

		$this->prepareBuilt();

		if ( $this->get( 'template' ) === '' ) {
			$rowTexts = $this->getRowTexts( 'getRowText' );
		} else {
			$rowTexts = $this->getRowTexts( 'getRowTextFromTemplate' );
		}

		return
			$this->getTemplateCall( 'introtemplate' ) .
			$this->get( 'result-open-tag' ) .

			join( $this->get( 'sep' ), $rowTexts ) .

			$this->get( 'result-close-tag' ) .
			$this->getTemplateCall( 'outrotemplate' );
	}

	protected function prepareBuilt() {

		$format = $this->getEffectiveFormat();

		if ( $this->get( 'template' ) !== '' ) {

			$this->set( [ 'value-open-tag' => '', 'value-close-tag' => '' ] );

			if ( $format === 'list' ) {
				$format = 'template';
			}

		}

		$this->configuration = array_merge( 
			self::$defaultConfigurations[ '*' ], 
			self::$defaultConfigurations[ $format ], 
			$this->getDefaultsFromI18N( $format ), 
			$this->configuration );

	}

	/**
	 * @return string
	 */
	protected function getEffectiveFormat() {

		$format = $this->get( 'format' );

		if ( $format !== 'template' && array_key_exists( $format, self::$defaultConfigurations ) ) {
			return $format;
		}

		return 'list';
	}

	/**
	 * @param string $format
	 *
	 * @return string[]
	 */
	protected function getDefaultsFromI18N( $format ) {
		return [
			'sep' => ( $format === 'list' ) ? Message::get( 'smw-format-list-separator' ) : '',
			'propsep' => Message::get( 'smw-format-list-property-separator' ),
			'valsep' => Message::get( 'smw-format-list-value-separator' ),
			'field-label-separator' => Message::get( 'smw-format-list-field-label-separator' ),
			'other-fields-open' => Message::get( 'smw-format-list-other-fields-open' ),
			'other-fields-close' => Message::get( 'smw-format-list-other-fields-close' ),
		];
	}

	/**
	 * @param string $getRowTextFunctionName
	 *
	 * @return string[]
	 */
	protected function getRowTexts( $getRowTextFunctionName ) {

		$rowTexts = [];

		$queryResult = $this->getQueryResult();
		$queryResult->reset();

		$num = $queryResult->getQuery()->getOffset();

		while ( ( $row = $queryResult->getNext() ) !== false ) {
			$num++;
			$rowTexts[] = call_user_func( [ $this, $getRowTextFunctionName ], $row, $num );
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
	 * Returns text for one result row, formatted as a template call.
	 *
	 * @param SMWResultArray[] $fields
	 * @param int $rownum
	 *
	 * @return string
	 */
	protected function getRowTextFromTemplate( array $fields, $rownum = 0 ) {

		$templateRenderer = $this->getTemplateRenderer();

		foreach ( $fields as $column => $field ) {

			$fieldLabel = $this->getFieldLabelForTemplate( $field, $column );
			$fieldText = $this->getValuesText( $field, $column );

			$templateRenderer->addField( $fieldLabel, $fieldText );
		}

		$templateRenderer->addField( '#rownumber', $rownum );
		$templateRenderer->packFieldsForTemplate( $this->get( 'template' ) );

		return
			$this->get( 'row-open-tag' ) .
			$templateRenderer->render() .
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

			$valuesText = $this->getValuesText( $field, $columnNumber );

			if ( $valuesText !== '' ) {
				$fieldTexts[] =
					$this->get( 'field-open-tag' ) .
					$this->getFieldLabel( $field ) .
					$valuesText .
					$this->get( 'field-close-tag' );
			}

			$columnNumber++;
		}

		return $fieldTexts;
	}

	/**
	 * @param SMWResultArray $field
	 * @return string
	 */
	protected function getFieldLabel( SMWResultArray $field ) {

		if ( $this->get( 'show-headers' ) === SMW_HEADERS_HIDE || $field->getPrintRequest()->getLabel() === '' ) {
			return '';
		}

		$linker = $this->get( 'show-headers' ) === SMW_HEADERS_PLAIN ? null : $this->linker;

		return
			$this->get( 'field-label-open-tag' ) .
			$field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, $linker ) .
			$this->get( 'field-label-close-tag' ) .
			$this->get( 'field-label-separator' );

	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 *
	 * @return string
	 */
	protected function getFieldLabelForTemplate( SMWResultArray $field, $column ) {

		if ( $this->get( 'named args' ) === false ) {
			return intval( $column + 1 );
		}

		$label = $field->getPrintRequest()->getLabel();

		if ( $label === '' ) {
			return intval( $column + 1 );
		}

		return $label;
	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 * @return string
	 */
	protected function getValuesText( SMWResultArray $field, $column = 0 ) {

		$valueTexts = $this->getValueTexts( $field, $column );

		return join( $this->get( 'valsep' ), $valueTexts );

	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 * @return string[]
	 */
	protected function getValueTexts( SMWResultArray $field, $column ) {

		$valueTexts = [];

		$field->reset();
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
	 * @param int $columnNumber Column number
	 *
	 * @return \Linker|null
	 */
	protected function getLinkerForColumn( $columnNumber ) {

		if ( ( $columnNumber === 0 && $this->get( 'link-first' ) ) ||
			( $columnNumber > 0 && $this->get( 'link-others' ) ) ) {
			return $this->linker;
		}

		return null;
	}

	/**
	 * @param string $param
	 *
	 * @return string
	 */
	protected function getTemplateCall( $param ) {

		$templatename = $this->get( $param );

		if ( $templatename !== '' ) {

			$templateRenderer = $this->getTemplateRenderer();

			$templateRenderer->packFieldsForTemplate( $templatename );
			return $templateRenderer->render();
		}

		return '';
	}

	/**
	 * @return WikitextTemplateRenderer
	 */
	protected function getTemplateRenderer() {

		if ( $this->templateRenderer === null ) {
			$this->templateRenderer = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newWikitextTemplateRenderer();
			$this->addCommonTemplateFields( $this->templateRenderer );
		}

		return clone( $this->templateRenderer );
	}

	/**
	 * @param WikitextTemplateRenderer $templateRenderer
	 */
	protected function addCommonTemplateFields( WikitextTemplateRenderer $templateRenderer ) {

		$userParam = $this->get( 'userparam' );

		if ( $userParam !== '' ) {

			$templateRenderer->addField(
				'#userparam',
				$userParam
			);
		}

		$templateRenderer->addField(
			'#querycondition',
			$this->getQueryResult()->getQuery()->getQueryString()
		);

		$templateRenderer->addField(
			'#querylimit',
			$this->getQueryResult()->getQuery()->getLimit()
		);

		$templateRenderer->addField(
			'#resultoffset',
			$this->getQueryResult()->getQuery()->getOffset()
		);

		$templateRenderer->addField(
			'#rowcount',
			$this->getRowCount()
			//$this->getQueryResult()->getCount()  // FIXME: Re-activate if another query takes too long.
		);
	}

	/**
	 * @return int
	 */
	protected function getRowCount(){

		if ( $this->numberOfPages === null ) {

			$countQuery = \SMWQueryProcessor::createQuery( $this->getQueryResult()->getQueryString(), \SMWQueryProcessor::getProcessedParams( [] ) );
			$countQuery->querymode = \SMWQuery::MODE_COUNT;

			$queryResult = $this->getQueryResult()->getStore()->getQueryResult( $countQuery );
			$this->numberOfPages = $queryResult instanceof \SMWQueryResult ? $queryResult->getCountValue() : $queryResult;

		}

		return $this->numberOfPages;
	}

}