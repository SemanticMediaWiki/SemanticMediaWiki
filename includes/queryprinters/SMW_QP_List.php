<?php
/**
 * Print query results in lists.
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
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * New implementation of SMW's printer for results in lists.
 *
 * Somewhat confusing code, since one has to iterate through lists, inserting texts
 * in between their elements depending on whether the element is the first that is
 * printed, the first that is printed in parentheses, or the last that will be printed.
 * Maybe one could further simplify this.
 *
 * @ingroup SMWQuery
 */
class SMWListResultPrinter extends SMWResultPrinter {

	protected $mSep;
	protected $mTemplate;
	protected $mNamedArgs;
	protected $mUserParam;
	protected $mColumns;
	protected $mIntroTemplate;
	protected $mOutroTemplate;
	
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
		
		$this->mSep = str_replace( '_', ' ', $params['sep'] ); // allow "_" for encoding spaces, as documented
		$this->mTemplate = trim( $params['template'] );
		$this->mNamedArgs = $params['named args'];
		$this->mUserParam = trim( $params['userparam'] );
		$this->mColumns = !$this->isPlainlist() ? $params['columns'] : 1;
		$this->mIntroTemplate = $params['introtemplate'];
		$this->mOutroTemplate = $params['outrotemplate'];
	}

	public function getName() {
		return wfMessage( 'smw_printername_' . $this->mFormat )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		if ( ( $this->mFormat == 'template' ) && ( $this->mTemplate == false ) ) {
			$res->addErrors( array( wfMessage( 'smw_notemplategiven' )->inContentLanguage()->text() ) );
			return '';
		}
		
		// Determine mark-up strings used around list items:
		if ( ( $this->mFormat == 'ul' ) || ( $this->mFormat == 'ol' ) ) {
			$header = "<" . $this->mFormat . ">\n";
			$footer = "</" . $this->mFormat . ">\n";
			$rowstart = "\t<li>";
			$rowend = "</li>\n";
			$plainlist = false;
		} else { // "list" and "template" format
			$header = '';
			$footer = '';
			$rowstart = '';
			$rowend = '';
			$plainlist = true;
			
		}
		
		if ( $this->mSep !== '' ) { // always respect custom separator
			$listsep = $this->mSep;
			$finallistsep = $listsep;
		} elseif ( $this->mFormat == 'list' )  {  // make default list ", , , and "
			// TODO: No default separator for "ul" and "ol" to not break
			// compatibility with SMW pre 1.7.1. But they really should have
			// default separators, i.e. the check should be for
			// $this->mFormat !== 'template'
			
			$listsep = ', ';
			$finallistsep = wfMessage( 'smw_finallistconjunct' )->inContentLanguage()->text() . ' ';
		} else { // no default separators for format "template", "ul", "ol"
			$listsep = '';
			$finallistsep = '';
		}
		
		// Initialise more values
		$result = '';
		$column_width = 0;
		$rows_per_column = -1; // usually unnecessary
		$rows_in_cur_column = -1;
		
		// Set up floating divs, if there's more than one column
		if ( $this->mColumns > 1 ) {
			$column_width = floor( 100 / $this->mColumns );
			$result .= '<div style="float: left; width: ' . $column_width . '%">' . "\n";
			$rows_per_column = ceil( $res->getCount() / $this->mColumns );
			$rows_in_cur_column = 0;
		}

		if ( $header !== '' ) {
			$result .= $header;
		} 
		
		if ( $this->mIntroTemplate !== '' ) {
			$result .= "{{" . $this->mIntroTemplate . "}}";
		}

		// Now print each row
		$rownum = -1;
		while ( $row = $res->getNext() ) {
			$this->printRow( $row, $rownum, $rows_in_cur_column,
				$rows_per_column, $this->mFormat, $plainlist,
				$header, $footer, $rowstart, $rowend, $result,
				$column_width, $res, $listsep, $finallistsep );
		}
		
		if ( $this->mOutroTemplate !== '' ) {
			$result .= "{{" . $this->mOutroTemplate . "}}";
		}
		
		// Make label for finding further results
		if ( $this->linkFurtherResults( $res ) && ( ( $this->mFormat != 'ol' ) || ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) ) ) {
			$this->showFurtherResults( $result, $res, $rowstart, $rowend, $outputmode );
		}

		// Print footer
		if ( $footer !== '' ) {
			$result .= $footer;
		}
		
		if ( $this->mColumns > 1 ) {
			$result .= "</div>\n";
		}
		
		if ( $this->mColumns > 1 ) {
			$result .= '<br style="clear: both" />' . "\n";
		}

		// Make sure that if the result set turns empty and if available display default
		if ( $this->params['default'] !== '' && $result === '' ) {
			$result = $this->params['default'];
		}

		return $result;
	}
	
	protected function printRow( $row, &$rownum, &$rows_in_cur_column,
		$rows_per_column, $format, $plainlist, $header, $footer,
		$rowstart, $rowend, &$result, $column_width, $res, $listsep,
		$finallistsep ) {

		$rownum++;
		
		if ( $this->mColumns > 1 ) {
			if ( $rows_in_cur_column == $rows_per_column ) {
				// If it's a numbered list, and it's split
				// into columns, add in the 'start='
				// attribute so that each additional column
				// starts at the right place. This attribute
				// is actually deprecated, but it appears to
				// still be supported by the major browsers...
				if ( $format == 'ol' ) {
					$header = "<ol start=\"" . ( $rownum + 1 ) . "\">";
				}
				$result .= <<<END

				$footer
				</div>
				<div style="float: left; width: $column_width%">
				$header

END;
				$rows_in_cur_column = 0;
			}
			
			$rows_in_cur_column++;
		}
		
		if ( $rownum > 0 && $plainlist )  {
			$result .=  ( $rownum <= $res->getCount() ) ? $listsep : $finallistsep; // the comma between "rows" other than the last one
		} else {
			$result .= $rowstart;
		}

		if ( $this->mTemplate !== '' ) { // build template code
			$this->hasTemplates = true;
			$wikitext = ( $this->mUserParam ) ? "|userparam=$this->mUserParam" : '';
			
			foreach ( $row as $i => $field ) {
				$wikitext .= '|' . ( $this->mNamedArgs ? '?' . $field->getPrintRequest()->getLabel() : $i + 1 ) . '=';
				$first_value = true;
				
				while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $i == 0 ) ) ) !== false ) {
					if ( $first_value ) $first_value = false; else $wikitext .= ', ';
					$wikitext .= $text;
				}
			}
			
			$wikitext .= "|#=$rownum";
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
						$result .= "$listsep ";
					}
					
					if ( $first_value ) { // first value in any column, print header
						$first_value = false;
						
						if ( ( $this->mShowHeaders != SMW_HEADERS_HIDE ) && ( $field->getPrintRequest()->getLabel() !== '' ) ) {
							$result .= $field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null:$this->mLinker ) ) . ' ';
						}
					}
					
					$result .= Sanitizer::stripAllTags( $text ); // actual output value
				}
				
				$first_col = false;
			}
			
			if ( $found_values ) $result .= ')';
		}
		
		$result .= $rowend;
	}
	
	protected function showFurtherResults( &$result, $res, $rowstart, $rowend, $outputMode ) {
		$link = $this->getFurtherResultsLink( $res, $outputMode );
		$result .= $rowstart . ' '. $link->getText( SMW_OUTPUT_WIKI, $this->mLinker ) . $rowend;
	}

	protected function isPlainlist() {
		return $this->mFormat != 'ul' && $this->mFormat != 'ol';
	}
	
	public function getParameters() {
		$params = parent::getParameters();

		$params[] = array(
			'name' => 'sep',
			'message' => 'smw-paramdesc-sep',
			'default' => '',
		);

		$params[] = array(
			'name' => 'template',
			'message' => 'smw-paramdesc-template',
			'default' => '',
		);

		$params[] = array(
			'name' => 'named args',
			'type' => 'boolean',
			'message' => 'smw-paramdesc-named_args',
			'default' => false,
		);

		if ( !$this->isPlainlist() ) {
			$params[] = array(
				'name' => 'columns',
				'type' => 'integer',
				'message' => 'smw-paramdesc-columns',
				'default' => 1,
				'range' => array( 1, 10 ),
			);
		}

		$params[] = array(
			'name' => 'userparam',
			'message' => 'smw-paramdesc-userparam',
			'default' => '',
		);

		$params[] = array(
			'name' => 'introtemplate',
			'message' => 'smw-paramdesc-introtemplate',
			'default' => '',
		);

		$params[] = array(
			'name' => 'outrotemplate',
			'message' => 'smw-paramdesc-outrotemplate',
			'default' => '',
		);

		return $params;
	}

}
