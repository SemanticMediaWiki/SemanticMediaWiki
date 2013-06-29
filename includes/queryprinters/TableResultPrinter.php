<?php

namespace SMW;

use SMWResultArray;
use SMWQueryResult;
use SMWQueryProcessor;
use SMWPrintRequest;

/**
 * Print query results in tables
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.5.3
 *
 * @file
 *
 * @license GNU GPL v2 or later
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */

/**
 * Print query results in tables
 *
 * @ingroup QueryPrinter
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
		$this->tableFormatter = new TableFormatter( $this->isHTML );

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

				$this->tableFormatter->addTableHeader( ( $text === '' ? '&nbsp;' : $text ), $attribs );
			}
		}

		while ( $subject = $res->getNext() ) {
			$this->getRowForSubject( $subject, $outputmode, $columnClasses );
			$this->tableFormatter->addTableRow();
		}

		// print further results footer
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $this->getFurtherResultsLink( $res, $outputmode );

			$this->tableFormatter->addTableCell(
					$link->getText( $outputmode, $this->mLinker ),
					array( 'class' => 'sortbottom', 'colspan' => $res->getColumnCount() )
			);
			$this->tableFormatter->addTableRow( array( 'class' => 'smwfooter' ) );
		}

		$tableAttrs = array( 'class' => $this->params['class'] );

		if ( $this->mFormat == 'broadtable' ) {
			$tableAttrs['width'] = '100%';
		}

		// @note A table is only transposable if header elements are visible
		// $this->mShowHeaders !== SMW_HEADERS_HIDE && $this->params['transpose']
		// if transpose is enabled, please adopt the unit test as well
		return $this->tableFormatter->transpose( false )->getTable( $tableAttrs );
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

		$this->tableFormatter->addTableCell( $content, $attribs );
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
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['class'] = array(
			'name' => 'class',
			'message' => 'smw-paramdesc-table-class',
			'default' => 'sortable wikitable smwtable',
		);

		// Uncomment to enable this feature
		// $params['transpose'] = array(
		//	'type' => 'boolean',
		//	'default' => false,
		//	'message' => 'smw-paramdesc-table-transpose',
		// );

		return $params;
	}
}

/**
 * SMWTableResultPrinter
 * @codeCoverageIgnore
 *
 * @deprecated since SMW 1.9
 */
class_alias( 'SMW\TableResultPrinter', 'SMWTableResultPrinter' );
