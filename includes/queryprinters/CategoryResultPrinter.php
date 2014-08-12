<?php

namespace SMW;

use SMWDataItem;
use SMWQueryResult;

/**
 * Print query results in alphabetic groups displayed in columns, a la the
 * standard Category pages and the default view in Semantic Drilldown.
 * Based on SMW_QP_List by Markus Krötzsch.
 *
 * @ingroup SMWQuery
 *
 * @author David Loomer
 * @author Yaron Koren
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CategoryResultPrinter extends ResultPrinter {

	protected $mDelim;
	protected $mTemplate;
	protected $mUserParam;
	protected $mNumColumns;

	/**
	 * @see SMWResultPrinter::handleParameters
	 *
	 * @since 1.6.2
	 *
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );

		$this->mUserParam = trim( $params['userparam'] );
		$this->mDelim = trim( $params['delim'] );
		$this->mNumColumns = $params['columns'];
		$this->mTemplate = $params['template'];
	}

	public function getName() {
		return wfMessage( 'smw_printername_' . $this->mFormat )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		global $wgContLang;

		$result = '';
		$num = $res->getCount();

		$prev_first_char = "";
		$rows_per_column = ceil( $num / $this->mNumColumns );
		// column width is a percentage
		$column_width = floor( 100 / $this->mNumColumns );

		// Print all result rows:
		$rowindex = 0;
		$row = $res->getNext();

		while ( $row !== false ) {
			$nextrow = $res->getNext(); // look ahead

			if ( !isset( $row[0] ) ) {
				$row = $nextrow;
				continue;
			}

			$content = $row[0]->getContent();

			if ( !isset( $content[0] ) || !( $content[0] instanceof SMWDataItem ) ) {
				$row = $nextrow;
				continue;
			}

			$cur_first_char = $wgContLang->firstChar(
				$content[0]->getDIType() == SMWDataItem::TYPE_WIKIPAGE ?
					$res->getStore()->getWikiPageSortKey( $content[0] )
					: $content[0]->getSortKey()
			);

			if ( $rowindex % $rows_per_column == 0 ) {
				$result .= "\n			<div style=\"float: left; width: $column_width%;\">\n";
				if ( $cur_first_char == $prev_first_char )
					$result .= "				<h3>$cur_first_char " . wfMessage( 'listingcontinuesabbrev' )->text() . "</h3>\n				<ul>\n";
			}

			// if we're at a new first letter, end
			// the last list and start a new one
			if ( $cur_first_char != $prev_first_char ) {
				if ( $rowindex % $rows_per_column > 0 )
					$result .= "				</ul>\n";
				$result .= "				<h3>$cur_first_char</h3>\n				<ul>\n";
			}
			$prev_first_char = $cur_first_char;

			$result .= '<li>';
			$first_col = true;

			if ( $this->mTemplate !== '' ) { // build template code
				$this->hasTemplates = true;
				$wikitext = ( $this->mUserParam ) ? "|userparam=$this->mUserParam":'';
				$i = 1; // explicitly number parameters for more robust parsing (values may contain "=")

				foreach ( $row as $field ) {
					$wikitext .= '|' . $i++ . '=';
					$first_value = true;

					while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {
						if ( $first_value ) $first_value = false; else $wikitext .= $this->mDelim . ' ';
						$wikitext .= $text;
					}

					$first_col = false;
				}

				$wikitext .= "|#=$rowindex";
				$result .= '{{' . $this->mTemplate . $wikitext . '}}';
				// str_replace('|', '&#x007C;', // encode '|' for use in templates (templates fail otherwise) -- this is not the place for doing this, since even DV-Wikitexts contain proper "|"!
			} else {  // build simple list
				$first_col = true;
				$found_values = false; // has anything but the first column been printed?

				foreach ( $row as $field ) {
					$first_value = true;

					while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {
						if ( !$first_col && !$found_values ) { // first values after first column
							$result .= ' (';
							$found_values = true;
						} elseif ( $found_values || !$first_value ) {
							// any value after '(' or non-first values on first column
							$result .= ', ';
						}

						if ( $first_value ) { // first value in any column, print header
							$first_value = false;

							if ( $this->mShowHeaders && ( $field->getPrintRequest()->getLabel() !== '' ) ) {
								$result .= $field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, $this->mLinker ) . ' ';
							}
						}

						$result .= $text; // actual output value
					}

					$first_col = false;
				}

				if ( $found_values ) $result .= ')';
			}

			$result .= '</li>';
			$row = $nextrow;

			// end list if we're at the end of the column
			// or the page
			if ( ( $rowindex + 1 ) % $rows_per_column == 0 && ( $rowindex + 1 ) < $num ) {
				$result .= "				</ul>\n			</div> <!-- end column -->";
			}

			$rowindex++;
		}

		if ( $result === '' ) {

			$res->addErrors( array(
				$this->msg( 'smw-qp-empty-data' )->inContentLanguage()->text()
			) );

			return $result;
		}

		// Make label for finding further results
		if ( $this->linkFurtherResults( $res ) ) {
			$result .= '<br /><li>' . $this->getLink( $res, $outputmode )->getText( SMW_OUTPUT_WIKI, $this->mLinker ) . '</li>';
		}

		$result .= "</ul>\n</div> <!-- end column -->";
		// clear all the CSS floats
		$result .= "\n" . '<br style="clear: both;"/>';

		// <H3> will generate TOC entries otherwise.  Probably need another way
		// to accomplish this -- user might still want TOC for other page content.
		$result .= '__NOTOC__';

		return $result;
	}

	public function getParameters() {
		return array_merge( parent::getParameters(), array(
			array(
				'name' => 'columns',
				'type' => 'integer',
				'message' => 'smw-paramdesc-columns',
				'default' => 3,
			),
			array(
				'name' => 'delim',
				'message' => 'smw-paramdesc-category-delim',
				'default' => '',
			),
			array(
				'name' => 'template',
				'message' => 'smw-paramdesc-category-template',
				'default' => '',
			),
			array(
				'name' => 'userparam',
				'message' => 'smw-paramdesc-category-userparam',
				'default' => '',
			),
		) );
	}

}
