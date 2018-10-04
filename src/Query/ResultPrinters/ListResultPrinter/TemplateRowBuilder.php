<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

use SMWResultArray;

/**
 * Class TemplateRowBuilder
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class TemplateRowBuilder extends RowBuilder {

	private $templateRendererFactory;

	/**
	 * TemplateRowBuilder constructor.
	 *
	 * @param TemplateRendererFactory $templateRendererFactory
	 */
	public function __construct( TemplateRendererFactory $templateRendererFactory ) {
		$this->templateRendererFactory = $templateRendererFactory;
	}

	/**
	 * Returns text for one result row, formatted as a template call.
	 *
	 * @param \SMWResultArray[] $fields
	 *
	 * @param int $rownum
	 *
	 * @return string
	 */
	public function getRowText( array $fields, $rownum = 0 ) {

		$templateRenderer = $this->templateRendererFactory->getTemplateRenderer();

		foreach ( $fields as $column => $field ) {

			$fieldLabel = $this->getFieldLabel( $field, $column );
			$fieldText = $this->getValueTextsBuilder()->getValuesText( $field, $column );

			$templateRenderer->addField( $fieldLabel, $fieldText );
		}

		/** @deprecated since SMW 3.0 */
		$templateRenderer->addField( '#', $rownum );

		$templateRenderer->addField( '#rownumber', $rownum + 1 );
		$templateRenderer->packFieldsForTemplate( $this->get( 'template' ) );

		return $templateRenderer->render();

	}

	/**
	 * @param SMWResultArray $field
	 * @param int $column
	 *
	 * @return string
	 */
	private function getFieldLabel( SMWResultArray $field, $column ) {

		if ( $this->get( 'named args' ) === false ) {
			return intval( $column + 1 );
		}

		$label = $field->getPrintRequest()->getLabel();

		if ( $label === '' ) {
			return intval( $column + 1 );
		}

		return $label;
	}

}