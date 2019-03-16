<?php

namespace SMW\Query\ResultPrinters;

use Html;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\Query\PrintRequest;
use SMW\Query\QueryStringifier;
use SMW\Utils\HtmlTable;
use SMWDataValue;
use SMWDIBlob as DIBlob;
use SMWQueryResult as QueryResult;
use SMWResultArray as ResultArray;

/**
 * Print query results in tables
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class TableResultPrinter extends ResultPrinter {

	/**
	 * @var HtmlTable
	 */
	private $htmlTable;

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->msg( 'smw_printername_' . $this->mFormat )->text();
	}

	/**
	 * @see ResultPrinter::isDeferrable
	 *
	 * {@inheritDoc}
	 */
	public function isDeferrable() {
		return true;
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {

		$params = parent::getParamDefinitions( $definitions );

		$params['class'] = [
			'name' => 'class',
			'message' => 'smw-paramdesc-table-class',
			'default' => 'sortable wikitable smwtable',
		];

		$params['transpose'] = [
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-table-transpose',
		];

		$params['sep'] = [
			'message' => 'smw-paramdesc-sep',
			'default' => '',
		];

		return $params;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {

		$this->isHTML = ( $outputMode === SMW_OUTPUT_HTML );
		$this->isDataTable = false;
		$class = isset( $this->params['class'] ) ? $this->params['class'] : '';

		if ( strpos( $class, 'datatable' ) !== false && $this->mShowHeaders !== SMW_HEADERS_HIDE ) {
			$this->isDataTable = true;
		}

		$this->htmlTable = new HtmlTable();

		$columnClasses = [];
		$headerList = [];

		// Default cell value separator
		if ( !isset( $this->params['sep'] ) || $this->params['sep'] === '' ) {
			$this->params['sep'] = '<br>';
		}

		 // building headers
		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) {
			$isPlain = $this->mShowHeaders == SMW_HEADERS_PLAIN;
			foreach ( $res->getPrintRequests() as /* SMWPrintRequest */ $pr ) {
				$attributes = [];
				$columnClass = str_replace( [ ' ', '_' ], '-', strip_tags( $pr->getText( SMW_OUTPUT_WIKI ) ) );
				$attributes['class'] = $columnClass;
				// Also add this to the array of classes, for
				// use in displaying each row.
				$columnClasses[] = $columnClass;

				// #2702 Use a fixed output on a requested plain printout
				$mode = $this->isHTML && $isPlain ? SMW_OUTPUT_WIKI : $outputMode;
				$text = $pr->getText( $mode, ( $isPlain ? null : $this->mLinker ) );
				$headerList[] = $pr->getCanonicalLabel();
				$this->htmlTable->header( ( $text === '' ? '&nbsp;' : $text ), $attributes );
			}
		}

		$rowNumber = 0;

		while ( $subject = $res->getNext() ) {
			$rowNumber++;
			$this->getRowForSubject( $subject, $outputMode, $columnClasses );

			$this->htmlTable->row(
				[
					'data-row-number' => $rowNumber
				]
			);
		}

		// print further results footer
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $this->getFurtherResultsLink( $res, $outputMode );

			$this->htmlTable->cell(
					$link->getText( $outputMode, $this->mLinker ),
					[ 'class' => 'sortbottom', 'colspan' => $res->getColumnCount() ]
			);

			$this->htmlTable->row( [ 'class' => 'smwfooter' ] );
		}

		$tableAttrs = [ 'class' => $class ];

		if ( $this->mFormat == 'broadtable' ) {
			$tableAttrs['width'] = '100%';
			$tableAttrs['class'] .= ' broadtable';
		}

		if ( $this->isDataTable ) {
			$this->addDataTableAttrs(
				$res,
				$headerList,
				$tableAttrs
			);
		}

		$transpose = $this->mShowHeaders !== SMW_HEADERS_HIDE && $this->params['transpose'];

		$html = $this->htmlTable->table(
			$tableAttrs,
			$transpose,
			$this->isHTML
		);

		if ( $this->isDataTable ) {

			// Simple approximation to avoid a massive text reflow once the DT JS
			// has finished processing the HTML table
			$count = $this->params['transpose'] ? $res->getColumnCount() : $res->getCount();
			$height = ( min( ( $count + ( $res->hasFurtherResults() ? 1 : 0 ) ), 10 ) * 50 ) + 40;

			$html = Html::rawElement(
				'div',
				[
					'class' => 'smw-datatable smw-placeholder is-disabled smw-flex-center' . (
						$this->params['class'] !== '' ? ' ' . $this->params['class'] : ''
					),
					'style'     => "height:{$height}px;"
				],
				Html::rawElement(
					'span',
					[
						'class' => 'smw-overlay-spinner medium flex'
					]
				) . $html
			);
		}

		return $html;
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
	protected function getCellForPropVals( ResultArray $resultArray, $outputMode, $columnClass ) {
		/** @var SMWDataValue[] $dataValues */
		$dataValues = [];

		while ( ( $dv = $resultArray->getNextDataValue() ) !== false ) {
			$dataValues[] = $dv;
		}

		$printRequest = $resultArray->getPrintRequest();
		$printRequestType = $printRequest->getTypeID();

		$cellTypeClass = " smwtype$printRequestType";

		// We would like the cell class to always be defined, even if the cell itself is empty
		$attributes = [
			'class' => $columnClass . $cellTypeClass
		];

		$content = null;

		if ( count( $dataValues ) > 0 ) {
			$sortKey = $dataValues[0]->getDataItem()->getSortKey();
			$dataValueType = $dataValues[0]->getTypeID();

			// The data value type might differ from the print request type - override in this case
			if ( $dataValueType !== '' && $dataValueType !== $printRequestType ) {
				$attributes['class'] = "$columnClass smwtype$dataValueType";
			}

			if ( is_numeric( $sortKey ) ) {
				$attributes['data-sort-value'] = $sortKey;
			}

			if ( $this->isDataTable && $sortKey !== '' ) {
				$attributes['data-order'] = $sortKey;
			}

			$alignment = trim( $printRequest->getParameter( 'align' ) );

			if ( in_array( $alignment, [ 'right', 'left', 'center' ] ) ) {
				$attributes['style'] = "text-align:$alignment;";
			}

			$width = htmlspecialchars(
				trim( $printRequest->getParameter( 'width' ) ),
				ENT_QUOTES
			);

			if ( $width ) {
				$attributes['style'] = ( isset( $attributes['style'] ) ?  $attributes['style'] . ' ' : '' ) . "width:$width;";
			}

			$content = $this->getCellContent(
				$dataValues,
				$outputMode,
				$printRequest->getMode() == PrintRequest::PRINT_THIS
			);
		}

		// Sort the cell HTML attributes, to make test behavior more deterministic
		ksort( $attributes );

		$this->htmlTable->cell( $content, $attributes );
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
		$values = [];

		foreach ( $dataValues as $dv ) {

			// Restore output in Special:Ask on:
			// - file/image parsing
			// - text formatting on string elements including italic, bold etc.
			if ( $outputMode === SMW_OUTPUT_HTML && $dv->getDataItem() instanceof DIWikiPage && $dv->getDataItem()->getNamespace() === NS_FILE ||
				$outputMode === SMW_OUTPUT_HTML && $dv->getDataItem() instanceof DIBlob ) {
				// Too lazy to handle the Parser object and besides the Message
				// parse does the job and ensures no other hook is executed
				$value = Message::get(
					[ 'smw-parse', $dv->getShortText( SMW_OUTPUT_WIKI, $this->getLinker( $isSubject ) ) ],
					Message::PARSE
				);
			} else {
				$value = $dv->getShortText( $outputMode, $this->getLinker( $isSubject ) );
			}


			$values[] = $value === '' ? '&nbsp;' : $value;
		}

		$sep = strtolower( $this->params['sep'] );

		if ( !$isSubject && $sep === 'ul' && count( $values ) > 1 ) {
			$html = '<ul><li>' . implode( '</li><li>', $values ) . '</li></ul>';
		} elseif ( !$isSubject && $sep === 'ol' && count( $values ) > 1 ) {
			$html = '<ol><li>' . implode( '</li><li>', $values ) . '</li></ol>';
		} else {
			$html = implode( $this->params['sep'], $values );
		}

		return $html;
	}

	/**
	 * @see ResultPrinter::getResources
	 */
	protected function getResources() {

		$class = isset( $this->params['class'] ) ? $this->params['class'] : '';

		if ( strpos( $class, 'datatable' ) === false ) {
			return [
				'styles' => [
					'onoi.dataTables.styles',
					'smw.tableprinter.datatable.styles'
				]
			];
		}

		return [
			'modules' => [
				'smw.tableprinter.datatable'
			],
			'styles' => [
				'onoi.dataTables.styles',
				'smw.tableprinter.datatable.styles'
			]
		];
	}

	private function addDataTableAttrs( $res, $headerList, &$tableAttrs ) {

		$tableAttrs['width'] = '100%';
		$tableAttrs['style'] = 'opacity:.0; display:none;';

		$tableAttrs['data-column-sort'] = json_encode(
			[
				'list'  => $headerList,
				'sort'  => $this->params['sort'],
				'order' => $this->params['order']
			]
		);

		$tableAttrs['data-query'] = QueryStringifier::toJson(
			$res->getQuery()
		);
	}

}
