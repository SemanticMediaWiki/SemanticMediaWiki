<?php

namespace SMW;

use ParamProcessor\ParamDefinition;
use SMWDataValue;
use SMWResultArray;
use SMWQueryResult;
use SMW\Query\PrintRequest;

/**
 * Print query results in tables
 *
 * @since 1.5.3
 *
 * @license GNU GPL v2 or later
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class TableResultPrinter extends ResultPrinter {

	/**
	 * @var HtmlTableRenderer
	 */
	private $htmlTableRenderer;

	/**
	 * @note grep search smw_printername_table, smw_printername_broadtable
	 * @codeCoverageIgnore
	 *
	 * @return string
	 */
	public function getName() {
		return $this->msg( 'smw_printername_' . $this->mFormat )->text();
	}

	/**
	 * Returns a table
	 *
	 * @param SMWQueryResult $res
	 * @param integer $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $res, $outputMode ) {
		$this->isHTML = ( $outputMode === SMW_OUTPUT_HTML );

		$this->htmlTableRenderer = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newHtmlTableRenderer();
		$this->htmlTableRenderer->setHtmlContext( $this->isHTML );

		$columnClasses = array();

		// Default cell value separator
		if ( !isset( $this->params['sep'] ) || $this->params['sep'] === '' ) {
			$this->params['sep'] = '<br>';
		}

		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) { // building headers
			foreach ( $res->getPrintRequests() as /* SMWPrintRequest */ $pr ) {
				$attributes = array();
				$columnClass = str_replace( array( ' ', '_' ), '-', strip_tags( $pr->getText( SMW_OUTPUT_WIKI ) ) );
				$attributes['class'] = $columnClass;
				// Also add this to the array of classes, for
				// use in displaying each row.
				$columnClasses[] = $columnClass;
				$text = $pr->getText( $outputMode, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null : $this->mLinker ) );

				$this->htmlTableRenderer->addHeader( ( $text === '' ? '&nbsp;' : $text ), $attributes );
			}
		}

		while ( $subject = $res->getNext() ) {
			$this->getRowForSubject( $subject, $outputMode, $columnClasses );
			$this->htmlTableRenderer->addRow();
		}

		// print further results footer
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $this->getFurtherResultsLink( $res, $outputMode );

			$this->htmlTableRenderer->addCell(
					$link->getText( $outputMode, $this->mLinker ),
					array( 'class' => 'sortbottom', 'colspan' => $res->getColumnCount() )
			);
			$this->htmlTableRenderer->addRow( array( 'class' => 'smwfooter' ) );
		}

		$tableAttrs = array( 'class' => $this->params['class'] );

		if ( $this->mFormat == 'broadtable' ) {
			$tableAttrs['width'] = '100%';
		}

		$this->htmlTableRenderer->transpose( $this->mShowHeaders !== SMW_HEADERS_HIDE && $this->params['transpose'] );

		return $this->htmlTableRenderer->getHtml( $tableAttrs );
	}

	/**
	 * Gets a single table row for a subject, ie page.
	 *
	 * @since 1.6.1
	 *
	 * @param SMWResultArray[] $subject
	 * @param int $outputMode
	 * @param string[] $columnClasses
	 *
	 * @return string
	 */
	private function getRowForSubject( array $subject, $outputMode, array $columnClasses ) {
		foreach ( $subject as $i => $field ) {
			// $columnClasses will be empty if "headers=hide"
			// was set.
			if ( array_key_exists( $i, $columnClasses ) ) {
				$columnClass = $columnClasses[$i];
			} else {
				$columnClass = null;
			}

			$this->getCellForPropVals( $field, $outputMode, $columnClass );
		}
	}

	/**
	 * Gets a table cell for all values of a property of a subject.
	 *
	 * @since 1.6.1
	 *
	 * @param SMWResultArray $resultArray
	 * @param int $outputMode
	 * @param string $columnClass
	 *
	 * @return string
	 */
	protected function getCellForPropVals( SMWResultArray $resultArray, $outputMode, $columnClass ) {
		$dataValues = array();

		while ( ( $dv = $resultArray->getNextDataValue() ) !== false ) {
			$dataValues[] = $dv;
		}

		$attributes = array();
		$content = null;

		if ( count( $dataValues ) > 0 ) {
			$sortKey = $dataValues[0]->getDataItem()->getSortKey();
			$dataValueType = $dataValues[0]->getTypeID();

			if ( is_numeric( $sortKey ) ) {
				$attributes['data-sort-value'] = $sortKey;
			}

			$alignment = trim( $resultArray->getPrintRequest()->getParameter( 'align' ) );

			if ( in_array( $alignment, array( 'right', 'left', 'center' ) ) ) {
				$attributes['style'] = "text-align:' . $alignment . ';";
			}
			$attributes['class'] = $columnClass . ( $dataValueType !== '' ? ' smwtype' . $dataValueType : '' );

			$content = $this->getCellContent(
				$dataValues,
				$outputMode,
				$resultArray->getPrintRequest()->getMode() == PrintRequest::PRINT_THIS
			);
		}

		$this->htmlTableRenderer->addCell( $content, $attributes );
	}

	/**
	 * Gets the contents for a table cell for all values of a property of a subject.
	 *
	 * @since 1.6.1
	 *
	 * @param SMWDataValue[] $dataValues
	 * @param $outputMode
	 * @param boolean $isSubject
	 *
	 * @return string
	 */
	protected function getCellContent( array $dataValues, $outputMode, $isSubject ) {
		$values = array();

		foreach ( $dataValues as $dv ) {
			$value = $dv->getShortText( $outputMode, $this->getLinker( $isSubject ) );
			$values[] = $value;
		}

		return implode( $this->params['sep'], $values );
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 * @codeCoverageIgnore
	 *
	 * @since 1.8
	 *
	 * @param ParamDefinition[] $definitions
	 *
	 * @return array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['class'] = array(
			'name' => 'class',
			'message' => 'smw-paramdesc-table-class',
			'default' => 'sortable wikitable smwtable',
		);

		$params['transpose'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-table-transpose',
		);

		$params['sep'] = array(
			'message' => 'smw-paramdesc-sep',
			'default' => '',
		);

		return $params;
	}
}
