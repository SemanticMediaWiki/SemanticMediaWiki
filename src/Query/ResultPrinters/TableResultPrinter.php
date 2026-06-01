<?php

namespace SMW\Query\ResultPrinters;

use MediaWiki\Html\Html;
use SMW\DataItems\Blob;
use SMW\DataItems\WikiPage;
use SMW\DataValues\DataValue;
use SMW\DataValues\TimeValue;
use SMW\Localizer\Message;
use SMW\Query\PrintRequest;
use SMW\Query\QueryResult;
use SMW\Query\QueryStringifier;
use SMW\Query\Result\ResultArray;
use SMW\Utils\HtmlTable;

/**
 * Print query results in tables
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class TableResultPrinter extends ResultPrinter {

	/**
	 * Scaling factor used to turn the Julian Day date sort key into a
	 * decimal-free integer for `data-sort-value`. It matches the seven decimal
	 * places that JulianDay::format() keeps, so the integer is exact and
	 * preserves the original ordering. See #6830.
	 */
	private const DATE_SORT_FACTOR = 10000000;

	private ?HtmlTable $htmlTable = null;

	private ?bool $isDataTable = null;

	private ?PrefixParameterProcessor $prefixParameterProcessor = null;

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
	public function isDeferrable(): bool {
		return true;
	}

	/**
	 * @see ResultPrinter::dependsOnUserLanguage
	 *
	 * {@inheritDoc}
	 */
	public function dependsOnUserLanguage(): bool {
		return false;
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ): array {
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

		$params['prefix'] = [
			'message' => 'smw-paramdesc-prefix',
			'default' => 'none',
			'values' => [ 'all', 'subject', 'none', 'auto' ],
		];

		return $params;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {
		$this->prefixParameterProcessor = new PrefixParameterProcessor( $res->getQuery(),
			$this->params['prefix'] );

		$this->isHTML = ( $outputMode === SMW_OUTPUT_HTML );
		$this->isDataTable = false;
		$class = $this->params['class'] ?? '';

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
			foreach ( $res->getPrintRequests() as $pr ) {
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
				// $attributes['class'] is a CSS class built from the (stripped) column
				// label; HtmlTable escapes attributes once. Phan over-taints it via the
				// print-request label source.
				// @phan-suppress-next-line SecurityCheck-DoubleEscaped
				$this->htmlTable->header( ( $text === '' ? '&nbsp;' : $text ), $attributes );
			}
		}

		$rowNumber = 0;

		$subject = $res->getNext();
		while ( $subject ) {
			$rowNumber++;
			// $columnClasses are CSS classes built from the (stripped) column labels and
			// escaped once at the cell sink; phan over-taints them via the label source.
			// @phan-suppress-next-line SecurityCheck-DoubleEscaped
			$this->getRowForSubject( $subject, $outputMode, $columnClasses );

			$this->htmlTable->row(
				[
					'data-row-number' => $rowNumber
				]
			);
			$subject = $res->getNext();
		}

		// print further results footer
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $this->getFurtherResultsLink( $res, $outputMode );

			$this->htmlTable->cell(
					$link->getText( $outputMode, $this->mLinker ),
					[ 'colspan' => $res->getColumnCount() ]
			);

			$this->htmlTable->row( [ 'class' => 'smwfooter sortbottom' ] );
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

		$transpose = $this->mShowHeaders !== SMW_HEADERS_HIDE && ( $this->params['transpose'] ?? false );

		$html = $this->htmlTable->table(
			$tableAttrs,
			$transpose,
			$this->isHTML
		);

		if ( $this->isDataTable ) {

			// Simple approximation to avoid a massive text reflow once the DT JS
			// has finished processing the HTML table
			$count = ( $this->params['transpose'] ?? false ) ? $res->getColumnCount() : $res->getCount();
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
	 * @param ResultArray[] $subject
	 * @param int $outputMode
	 * @param string[] $columnClasses
	 *
	 * @return void
	 */
	private function getRowForSubject( array $subject, $outputMode, array $columnClasses ): void {
		foreach ( $subject as $i => $field ) {
			// $columnClasses will be empty if "headers=hide" was set.
			$this->getCellForPropVals( $field, $outputMode, $columnClasses[$i] ?? '' );
		}
	}

	/**
	 * Gets a table cell for all values of a property of a subject.
	 *
	 * @since 1.6.1
	 *
	 * @param ResultArray $resultArray
	 * @param int $outputMode
	 * @param string $columnClass
	 *
	 * @return void
	 */
	protected function getCellForPropVals( ResultArray $resultArray, $outputMode, string $columnClass ): void {
		/** @var DataValue[] $dataValues */
		$dataValues = [];

		$dv = $resultArray->getNextDataValue();
		while ( $dv !== false ) {
			$dataValues[] = $dv;
			$dv = $resultArray->getNextDataValue();
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

			// Dates expose their sort key as a Julian Day float (e.g. "2440618.5").
			// MediaWiki's locale-aware tablesorter treats the "." as a group
			// separator under locales such as German, strips it, and so sorts the
			// column by the number of fractional digits rather than chronologically
			// (#6830). Emit a decimal-free, locale-proof integer that preserves the
			// exact ordering of the Julian Day sort key instead.
			$sortValue = $sortKey;

			if ( $dataValues[0] instanceof TimeValue && is_numeric( $sortKey ) ) {
				$sortValue = (string)(int)round( (float)$sortKey * self::DATE_SORT_FACTOR );
			}

			if ( is_numeric( $sortKey ) ) {
				$attributes['data-sort-value'] = $sortValue;
			}

			if ( $this->isDataTable && $sortKey !== '' ) {
				$attributes['data-order'] = $sortValue;
			}

			$alignment = trim( $printRequest->getParameter( 'align' ) );

			if ( in_array( $alignment, [ 'right', 'left', 'center' ] ) ) {
				$attributes['style'] = "text-align:$alignment;";
			}

			$width = trim( $printRequest->getParameter( 'width' ) );

			if ( $width ) {
				$attributes['style'] = ( isset( $attributes['style'] ) ? $attributes['style'] . ' ' : '' ) . "width:$width;";
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
	 * @param DataValue[] $dataValues
	 * @param $outputMode
	 * @param bool $isSubject
	 *
	 * @return string
	 */
	protected function getCellContent( array $dataValues, $outputMode, $isSubject ): string {
		$useLongText = $this->prefixParameterProcessor->useLongText( $isSubject );
		$dataValueMethod = $useLongText ? 'getLongText' : 'getShortText';

		$values = [];
		foreach ( $dataValues as $dv ) {

			// Dates use the HTML accessor so the formatter's semantic <time>
			// element is emitted even when the table is produced in wiki output
			// mode (inline #ask). The <time> markup is valid in the parsed
			// wikitext, and HTML output (Special:Ask) is unchanged.
			if ( $dv instanceof TimeValue ) {
				$value = $useLongText
					? $dv->getLongHTMLText( $this->getLinker( $isSubject ) )
					: $dv->getShortHTMLText( $this->getLinker( $isSubject ) );

			// Restore output in Special:Ask on:
			// - file/image parsing
			// - text formatting on string elements including italic, bold etc.
			} elseif ( ( $outputMode === SMW_OUTPUT_HTML && $dv->getDataItem() instanceof WikiPage && $dv->getDataItem()->getNamespace() === NS_FILE ) ||
				( $outputMode === SMW_OUTPUT_HTML && $dv->getDataItem() instanceof Blob ) ) {
				// Too lazy to handle the Parser object and besides the Message
				// parse does the job and ensures no other hook is executed
				$value = Message::get(
					[ 'smw-parse', $dv->$dataValueMethod( SMW_OUTPUT_WIKI, $this->getLinker( $isSubject ) ) ],
					Message::PARSE
				);
			} else {
				$value = $dv->$dataValueMethod( $outputMode, $this->getLinker( $isSubject ) );
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
	protected function getResources(): array {
		$class = $this->params['class'] ?? '';

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

	private function addDataTableAttrs( QueryResult $res, array $headerList, array &$tableAttrs ): void {
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
