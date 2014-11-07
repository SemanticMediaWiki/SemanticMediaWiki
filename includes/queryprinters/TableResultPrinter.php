<?php

namespace SMW;

use SMWResultArray;
use SMWQueryResult;
use SMWQueryProcessor;
use SMWPrintRequest;

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
	 * @param $outputmode integer
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		$result = '';

		$this->isHTML = ( $outputmode === SMW_OUTPUT_HTML );

		$this->tableBuilder = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newHtmlTableBuilder();
		$this->tableBuilder->setHtmlContext( $this->isHTML );

		$columnClasses = array();

		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) { // building headers
			$headers = array();

			foreach ( $res->getPrintRequests() as /* SMWPrintRequest */ $pr ) {
				$attribs = array();
				$columnClass = str_replace( array( ' ', '_' ), '-', strip_tags( $pr->getText( SMW_OUTPUT_WIKI ) ) );
				$attribs['class'] = $columnClass;
				// Also add this to the array of classes, for
				// use in displaying each row.
				$columnClasses[] = $columnClass;
				$text = $pr->getText( $outputmode, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null : $this->mLinker ) );

				$this->tableBuilder->addHeader( ( $text === '' ? '&nbsp;' : $text ), $attribs );
			}
		}

		while ( $subject = $res->getNext() ) {
			$this->getRowForSubject( $subject, $outputmode, $columnClasses );
			$this->tableBuilder->addRow();
		}

		// print further results footer
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $this->getFurtherResultsLink( $res, $outputmode );

			$this->tableBuilder->addCell(
					$link->getText( $outputmode, $this->mLinker ),
					array( 'class' => 'sortbottom', 'colspan' => $res->getColumnCount() )
			);
			$this->tableBuilder->addRow( array( 'class' => 'smwfooter' ) );
		}

		$tableAttrs = array( 'class' => $this->params['class'] );

		if ( $this->mFormat == 'broadtable' ) {
			$tableAttrs['width'] = '100%';
		}

		$this->tableBuilder->transpose( $this->mShowHeaders !== SMW_HEADERS_HIDE && $this->params['transpose'] );

		return $this->tableBuilder->getHtml( $tableAttrs );
	}

	/**
	 * Gets a single table row for a subject, ie page.
	 *
	 * @since 1.6.1
	 *
	 * @param array $subject
	 * @param $outputmode
	 *
	 * @return string
	 */
	protected function getRowForSubject( array /* of SMWResultArray */ $subject, $outputmode, $columnClasses ) {

		foreach ( $subject as $i => $field ) {
			// $columnClasses will be empty if "headers=hide"
			// was set.
			if ( array_key_exists( $i, $columnClasses ) ) {
				$columnClass = $columnClasses[$i];
			} else {
				$columnClass = null;
			}

			$this->getCellForPropVals( $field, $outputmode, $columnClass );
		}
	}

	/**
	 * Gets a table cell for all values of a property of a subject.
	 *
	 * @since 1.6.1
	 *
	 * @param SMWResultArray $resultArray
	 * @param $outputmode
	 *
	 * @return string
	 */
	protected function getCellForPropVals( SMWResultArray $resultArray, $outputmode, $columnClass ) {
		$dataValues = array();

		while ( ( $dv = $resultArray->getNextDataValue() ) !== false ) {
			$dataValues[] = $dv;
		}

		$attribs = array();
		$content = null;

		if ( count( $dataValues ) > 0 ) {
			$sortkey = $dataValues[0]->getDataItem()->getSortKey();
			$dataValueType = $dataValues[0]->getTypeID();

			if ( is_numeric( $sortkey ) ) {
				$attribs['data-sort-value'] = $sortkey;
			}

			$alignment = trim( $resultArray->getPrintRequest()->getParameter( 'align' ) );

			if ( in_array( $alignment, array( 'right', 'left', 'center' ) ) ) {
				$attribs['style'] = "text-align:' . $alignment . ';";
			}
			$attribs['class'] = $columnClass . ( $dataValueType !== '' ? ' smwtype' . $dataValueType : '' );

			$content = $this->getCellContent(
				$dataValues,
				$outputmode,
				$resultArray->getPrintRequest()->getMode() == SMWPrintRequest::PRINT_THIS
			);
		}

		$this->tableBuilder->addCell( $content, $attribs );
	}

	/**
	 * Gets the contents for a table cell for all values of a property of a subject.
	 *
	 * @since 1.6.1
	 *
	 * @param array $dataValues
	 * @param $outputmode
	 * @param boolean $isSubject
	 *
	 * @return string
	 */
	protected function getCellContent( array /* of SMWDataValue */ $dataValues, $outputmode, $isSubject ) {
		$values = array();

		foreach ( $dataValues as $dv ) {
			$value = $dv->getShortText( $outputmode, $this->getLinker( $isSubject ) );
			$values[] = $value;
		}

		return implode( '<br />', $values );
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

		return $params;
	}
}
