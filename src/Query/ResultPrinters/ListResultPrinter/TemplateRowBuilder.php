<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

use SMW\Query\Result\ResultArray;

/**
 * Class TemplateRowBuilder
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class TemplateRowBuilder extends RowBuilder {

	/**
	 * TemplateRowBuilder constructor.
	 */
	public function __construct( private readonly TemplateRendererFactory $templateRendererFactory ) {
	}

	/**
	 * Returns text for one result row, formatted as a template call.
	 *
	 * @param ResultArray[] $fields
	 *
	 * @param int $rownum
	 *
	 * @return string
	 */
	public function getRowText( array $fields, $rownum = 0 ): string {
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
	 * @param ResultArray $field
	 * @param int $column
	 *
	 * @return string
	 */
	private function getFieldLabel( ResultArray $field, int|string $column ) {
		if ( $this->get( 'named args' ) === false ) {
			return (string)( $column + 1 );
		}

		$label = $field->getPrintRequest()->getLabel();

		if ( $label === '' ) {
			return (string)( $column + 1 );
		}

		return $label;
	}

}
