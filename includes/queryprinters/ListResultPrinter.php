<?php

namespace SMW;

use SMWQueryResult;
use SMWDataItem;
use SMWResultArray;

use Sanitizer;
use Html;

/**
 * Print query results in lists.
 *
 * @author Markus Krötzsch
 * @ingroup SMWQuery
 */

/**
 * New implementation of SMW's printer for results in lists.
 * The implementation covers comma-separated lists, ordered and unordered lists.
 * List items may be formatted using templates, and list output can be in
 * multiple columns (at least for ordered and unordered lists).
 *
 * In the code below, one list item (with all extra information displayed for
 * it) is called a "row", while one entry in this row is called a "field" to
 * avoid confusion with the "columns" that we have in multi-column display.
 * Every field may in turn contain many "values".
 *
 * @ingroup SMWQuery
 */
class ListResultPrinter extends ResultPrinter {

	protected $mTemplate;
	protected $mNamedArgs;
	protected $mUserParam;
	protected $mColumns;
	protected $mIntroTemplate;
	protected $mOutroTemplate;

	/**
	 * The text used to start the list.
	 * @var string
	 * @since 1.9
	 */
	protected $header;
	/**
	 * The text used to end the list.
	 * @var string
	 * @since 1.9
	 */
	protected $footer;
	/**
	 * The text used to start a row in the list.
	 * @var string
	 * @since 1.9
	 */
	protected $rowstart;
	/**
	 * The text used to end a row in the list.
	 * @var string
	 * @since 1.9
	 */
	protected $rowend;
	/**
	 * The text used to separate items in the list, other than the final
	 * one.
	 * @var string
	 * @since 1.9
	 */
	protected $listsep;
	/**
	 * The text used to separate the last item in the list from the rest.
	 * @var string
	 * @since 1.9
	 */
	protected $finallistsep;
	/**
	 * Width (in percent) of columns in multi-column display.
	 * @var integer
	 * @since 1.9
	 */
	protected $columnWidth;
	/**
	 * Number of results per column in multi-column display.
	 * @var integer
	 * @since 1.9
	 */
	protected $rowsPerColumn;
	/**
	 * Number of results in current column in multi-column display.
	 * @var integer
	 * @since 1.9
	 */
	protected $numRowsInColumn;
	/**
	 * Number of results printed so far (equals index of result
	 * to print next).
	 * @var integer
	 * @since 1.9
	 */
	protected $numRows;


	/**
	 * @see SMWResultPrinter::handleParameters
	 *
	 * @since 1.6
	 *
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );

		$this->mTemplate = trim( $params['template'] );
		$this->mNamedArgs = $params['named args'];
		$this->mUserParam = trim( $params['userparam'] );
		$this->mColumns = !$this->isPlainlist() ? $params['columns'] : 1;
		$this->mIntroTemplate = $params['introtemplate'];
		$this->mOutroTemplate = $params['outrotemplate'];
	}

	/**
	 * @see SMW\ResultPrinter::getName
	 *
	 */
	public function getName() {
		// Give grep a chance to find the usages:
		// smw_printername_list, smw_printername_ol,smw_printername_ul, smw_printername_template
		return $this->getContext()->msg( 'smw_printername_' . $this->mFormat )->text();
	}

	/**
	 * @see SMW\ResultPrinter::getResultText
	 *
	 * @param SMWQueryResult $queryResult
	 * @param $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $queryResult, $outputMode ) {
		if ( $this->mFormat == 'template' && !$this->mTemplate ) {
			$queryResult->addErrors( array(
				$this->getContext()->msg( 'smw_notemplategiven' )->inContentLanguage()->text()
			) );
			return '';
		}

		$this->templateRenderer = ApplicationFactory::getInstance()->newMwCollaboratorFactory()->newWikitextTemplateRenderer();

		$this->initializePrintingParameters( $queryResult );

		$result = '';

		// Set up floating divs if there's more than one column
		if ( $this->mColumns > 1 ) {
			$result .= '<div style="float: left; width: ' . $this->columnWidth . '%">' . "\n";
		}

		$result .= $this->header;

		if ( $this->mIntroTemplate !== '' ) {
			$this->addCommonTemplateFields( $queryResult );
			$this->templateRenderer->packFieldsForTemplate( $this->mIntroTemplate );
			$result .= $this->templateRenderer->render();
		}

		while ( $row = $queryResult->getNext() ) {
			$result .= $this->getRowText( $row, $queryResult );
		}

		if ( $this->mOutroTemplate !== '' ) {
			$this->addCommonTemplateFields( $queryResult );
			$this->templateRenderer->packFieldsForTemplate( $this->mOutroTemplate );
			$result .= $this->templateRenderer->render();
		}

		// Make label for finding further results
		if ( $this->linkFurtherResults( $queryResult ) &&
			( $this->mFormat != 'ol' || $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) ) {
			$result .= trim( $this->getFurtherResultsText( $queryResult, $outputMode ) );
		}

		$result .= $this->footer;

		if ( $this->mColumns > 1 ) {
			$result .= "</div>\n" . '<br style="clear: both" />' . "\n";
		}

		// Display default if the result is empty
		if ( $result == '' ) {
			$result = $this->params['default'];
		}

		return $result;
	}

	/**
	 * Initialize the internal parameters that should be used to print this
	 * list, and reset row counters.
	 *
	 * @since 1.9
	 * @param SMWQueryResult $queryResult
	 */
	protected function initializePrintingParameters( SMWQueryResult $queryResult ) {
		$this->numRows = 0;
		$this->numRowsInColumn = 0;
		$this->rowSortkey = '';

		$this->columnWidth = floor( 100 / $this->mColumns );
		$this->rowsPerColumn = ceil( $queryResult->getCount() / $this->mColumns );

		// Determine mark-up strings used around list items:
		if ( $this->mFormat == 'ul' || $this->mFormat == 'ol' ) {
			$this->header = "<" . $this->mFormat . ">\n";
			$this->footer = "</" . $this->mFormat . ">\n";
			$this->rowstart = "\t<li>";
			$this->rowend = "</li>\n";
		} else { // "list" and "template" format
			$this->header = '';
			$this->footer = '';
			$this->rowstart = '';
			$this->rowend = '';
		}

		// Define separators for list items
		if ( $this->params['format'] !== 'template' ){
			if ( $this->params['format'] === 'list' && $this->params['sep'] === ',' ){
				// Make default list ", , , and "
				$this->listsep = ', ';
				$this->finallistsep = $this->getContext()->msg( 'smw_finallistconjunct' )->inContentLanguage()->text() . ' ';
			} else {
				// Allow "_" for encoding spaces, as documented
				$this->listsep = str_replace( '_', ' ', $this->params['sep'] );
				$this->finallistsep = $this->listsep;
			}
		} else {
			// No default separators for format "template"
			$this->listsep = '';
			$this->finallistsep = '';
		}
	}

	/**
	 * Get result text for one result row as part of getResultText().
	 *
	 * @since 1.9
	 * @param SMWResultArray[] $row
	 * @param SMWQueryResult $res
	 * @return string
	 */
	protected function getRowText( array $row, SMWQueryResult $res ) {
		$result = '';

		// Start new column:
		if ( $this->numRowsInColumn == $this->rowsPerColumn ) {
			// If it's a numbered list, and it's split
			// into columns, add in the 'start='
			// attribute so that each additional column
			// starts at the right place. This attribute
			// is actually deprecated, but it appears to
			// still be supported by the major browsers...
			if ( $this->mFormat == 'ol' ) {
				$header = "<ol start=\"" . ( $this->numRows + 1 ) . "\">";
			} else {
				$header = $this->header;
			}

			$this->numRowsInColumn = 0;

			$result .= $this->footer . '</div>' .
				"<div style=\"float: left; width: {$this->columnWidth}%\">" .
				$header;
		}

		if ( $this->mTemplate !== '' ) { // Build template code
			$this->hasTemplates = true;

			$this->addTemplateContentFields( $row );
			$this->addCommonTemplateFields( $res );
			$this->templateRenderer->packFieldsForTemplate( $this->mTemplate );

			$result .= $this->getRowStart( $res ) . $this->templateRenderer->render();
		} else { // Build simple list
			$content = $this->getRowListContent( $row );
			$result .= $this->getRowStart( $res ) . $content;
		}

		$result .= $this->rowend;
		$this->numRows++;
		$this->numRowsInColumn++;
		$this->rowSortkey = '';

		return $result;
	}

	/**
	 * Returns row start element
	 *
	 * @since 1.9
	 *
	 * @param SMWQueryResult $res
	 *
	 * @return string
	 */
	protected function getRowStart( SMWQueryResult $res ) {

		if ( $this->numRows > 0 && $this->isPlainlist() )  {
			// Use comma between "rows" other than the last one:
			return ( $this->numRows <= $res->getCount() ) ? $this->listsep : $this->finallistsep;
		}

		if ( $this->rowSortkey !== '' ) {
			return "\t" . Html::openElement( 'li',
				array( 'data-sortkey' => mb_substr( $this->rowSortkey, 0, 1 ) )
			);
		}

		return $this->rowstart;
	}

	/**
	 * Returns text for one result row, formatted as a list.
	 *
	 * @since 1.9
	 * @todo The inner lists of values per field should use different separators.
	 * @todo Some spaces are hard-coded here; should probably be part of separators.
	 * @bug Bad HTML tag escaping with hardcoded exceptions (for datatype _qty)
	 *
	 * @param SMWResultArray[] $row
	 *
	 * @return string
	 */
	protected function getRowListContent( array $row ) {
		$firstField = true; // is this the first entry in this row?
		$extraFields = false; // has anything but the first field been printed?
		$result = '';

		foreach ( $row as $field ) {
			$firstValue = true; // is this the first value in this field?

			while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {

				// Add sortkey for all non-list formats
				if ( $firstField && $this->params['format'] !== 'list' &&
					$dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE  ) {
					$this->rowSortkey = StoreFactory::getStore()->getWikiPageSortKey( $dataValue->getDataItem() );
				}

				$text = $dataValue->getShortText( SMW_OUTPUT_WIKI, $this->getLinker( $firstField ) );

				if ( !$firstField && !$extraFields ) { // first values after first column
					$result .= ' (';
					$extraFields = true;
				} elseif ( $extraFields || !$firstValue ) {
					// any value after '(' or non-first values on first column
					$result .= $this->listsep . ' ';
				}

				if ( $firstValue ) { // first value in any field, print header
					$firstValue = false;

					if ( ( $this->mShowHeaders != SMW_HEADERS_HIDE ) && ( $field->getPrintRequest()->getLabel() !== '' ) ) {
						$result .= $field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null:$this->mLinker ) ) . ' ';
					}
				}

				// Display the text with tags for all non-list type outputs and
				// where the property is of type _qty (to ensure the highlighter
				// is displayed) but for others remove tags so that lists are
				// not distorted by unresolved in-text tags
				// FIXME This is a hack that limits extendibility of SMW datatypes
				// by giving _qty a special status that no other type can have.
				if ( $dataValue->getTypeID() === '_qty' || $this->isPlainlist() ) {
					$result .=  $text;
				} else {
					$result .= Sanitizer::stripAllTags( $text );
				}
			}

			$firstField = false;
		}
		if ( $extraFields ) {
			$result .= ')';
		}

		return $result;
	}

	/**
	 * Returns text for one result row, formatted as a template call.
	 *
	 * @since 1.9
	 *
	 * @param $row
	 *
	 * @return string
	 */
	protected function addTemplateContentFields( $row ) {

		foreach ( $row as $i => $field ) {

			$value = '';
			$fieldName = '';

			if ( $this->mNamedArgs ) {
				$fieldName = '?' . $field->getPrintRequest()->getLabel();
			}

			if ( $fieldName === '' || $fieldName === '?' ) {
				$fieldName = $fieldName . $i + 1;
			}

			while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $i == 0 ) ) ) !== false ) {
				$value .= $value === '' ? $text : $this->params['sep'] . ' ' . $text;
			}

			$this->templateRenderer->addField( $fieldName, $value );
		}

		$this->templateRenderer->addField( '#', $this->numRows );
	}

	protected function addCommonTemplateFields( $queryResult ) {

		if ( $this->mUserParam ) {
			$this->templateRenderer->addField( 'userparam', $this->mUserParam );
		}

		$this->templateRenderer->addField(
			'smw-resultquerycondition',
			$queryResult->getQuery()->getQueryString()
		);

		$this->templateRenderer->addField(
			'smw-resultquerylimit',
			$queryResult->getQuery()->getLimit()
		);

		$this->templateRenderer->addField(
			'smw-resultqueryoffset',
			$queryResult->getQuery()->getOffset()
		);
	}

	/**
	 * Get text for further results link. Used only during getResultText().
	 *
	 * @since 1.9
	 * @param SMWQueryResult $res
	 * @param integer $outputMode
	 * @return string
	 */
	protected function getFurtherResultsText( SMWQueryResult $res, $outputMode ) {
		$link = $this->getFurtherResultsLink( $res, $outputMode );
		return $this->rowstart . ' ' .
			$link->getText( SMW_OUTPUT_WIKI, $this->mLinker ) .
			$this->rowend;
	}

	protected function isPlainlist() {
		return $this->mFormat != 'ul' && $this->mFormat != 'ol';
	}

	public function getParameters() {
		$params = parent::getParameters();

		$params['sep'] = array(
			'message' => 'smw-paramdesc-sep',
			'default' => ',',
		);

		$params['template'] = array(
			'message' => 'smw-paramdesc-template',
			'default' => '',
		);

		$params['named args'] = array(
			'type' => 'boolean',
			'message' => 'smw-paramdesc-named_args',
			'default' => false,
		);

		if ( !$this->isPlainlist() ) {
			$params['columns'] = array(
				'type' => 'integer',
				'message' => 'smw-paramdesc-columns',
				'default' => 1,
				'range' => array( 1, 10 ),
			);
		}

		$params['userparam'] = array(
			'message' => 'smw-paramdesc-userparam',
			'default' => '',
		);

		$params['introtemplate'] = array(
			'message' => 'smw-paramdesc-introtemplate',
			'default' => '',
		);

		$params['outrotemplate'] = array(
			'message' => 'smw-paramdesc-outrotemplate',
			'default' => '',
		);

		$params['import-annotation'] = array(
			'message' => 'smw-paramdesc-import-annotation',
			'type' => 'boolean',
			'default' => false
		);

		return $params;
	}
}
