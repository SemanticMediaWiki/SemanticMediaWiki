<?php
/**
 * Print query results in lists.
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

	protected $mSep = '';
	protected $mTemplate = '';
	protected $mUserParam = '';
	protected $mColumns = 1;
	protected $mIntroTemplate = '';
	protected $mOutroTemplate = '';

	protected function readParameters( $params, $outputmode ) {
		parent::readParameters( $params, $outputmode );

		if ( array_key_exists( 'sep', $params ) ) {
			$this->mSep = str_replace( '_', ' ', $params['sep'] );
		}
		
		if ( array_key_exists( 'template', $params ) ) {
			$this->mTemplate = trim( $params['template'] );
		}
		
		if ( array_key_exists( 'userparam', $params ) ) {
			$this->mUserParam = trim( $params['userparam'] );
		}
		
		if ( array_key_exists( 'columns', $params ) ) {
			$columns = trim( $params['columns'] );
			if ( $columns > 1 && $columns <= 10 ) { // allow a maximum of 10 columns
				$this->mColumns = (int)$columns;
			}
		}
		
		if ( array_key_exists( 'introtemplate', $params ) ) {
			$this->mIntroTemplate = $params['introtemplate'];
		}
		
		if ( array_key_exists( 'outrotemplate', $params ) ) {
			$this->mOutroTemplate = $params['outrotemplate'];
		}
	}

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_' . $this->mFormat );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		if ( ( $this->mFormat == 'template' ) && ( $this->mTemplate == false ) ) {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$res->addErrors( array( wfMsgForContent( 'smw_notemplategiven' ) ) );
			return '';
		}
		
		// Determine mark-up strings used around list items:
		if ( ( $this->mFormat == 'ul' ) || ( $this->mFormat == 'ol' ) ) {
			$header = "<" . $this->mFormat . ">\n";
			$footer = "</" . $this->mFormat . ">\n";
			$rowstart = "\t<li>";
			$rowend = "</li>\n";
			$plainlist = false;
			$finallistsep = '';
			$listsep = '';
		} else { // "list" and "template" format
			$header = '';
			$footer = '';
			$rowstart = '';
			$rowend = '';
			$plainlist = true;
			
			if ( $this->mSep != '' ) { // always respect custom separator
				$listsep = $this->mSep;
				$finallistsep = $listsep;
			} elseif ( $this->mFormat == 'list' )  {  // make default list ", , , and "
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$listsep = ', ';
				$finallistsep = wfMsgForContent( 'smw_finallistconjunct' ) . ' ';
			} else { // no default separators for format "template"
				$listsep = '';
				$finallistsep = '';
			}
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

		if ( $header != '' ) {
			$result .= $header;
		} 
		
		if ( $this->mIntroTemplate != '' ) {
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
		
		if ( $this->mOutroTemplate != '' ) {
			$result .= "{{" . $this->mOutroTemplate . "}}";
		}
		
		// Make label for finding further results
		if ( $this->linkFurtherResults( $res ) && ( ( $this->mFormat != 'ol' ) || ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) ) ) {
			$this->showFurtherResults( $result, $res, $rowstart, $rowend );
		}

		// Print footer
		if ( $footer != '' ) {
			$result .= $footer;
		}
		
		if ( $this->mColumns > 1 ) {
			$result .= "</div>\n";
		}
		
		if ( $this->mColumns > 1 ) {
			$result .= '<br style="clear: both" />' . "\n";
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

		$first_col = true;
		if ( $this->mTemplate != '' ) { // build template code
			$this->hasTemplates = true;
			$wikitext = ( $this->mUserParam ) ? "|userparam=$this->mUserParam" : '';
			$i = 1; // explicitly number parameters for more robust parsing (values may contain "=")
			
			foreach ( $row as $field ) {
				$wikitext .= '|' . $i++ . '=';
				$first_value = true;
				
				while ( ( $text = $field->getNextText( SMW_OUTPUT_WIKI, $this->getLinker( $first_col ) ) ) !== false ) {
					if ( $first_value ) $first_value = false; else $wikitext .= ', ';
					$wikitext .= $text;
				}
				
				$first_col = false;
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
						
						if ( ( $this->mShowHeaders != SMW_HEADERS_HIDE ) && ( $field->getPrintRequest()->getLabel() != '' ) ) {
							$result .= $field->getPrintRequest()->getText( SMW_OUTPUT_WIKI, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null:$this->mLinker ) ) . ' ';
						}
					}
					
					$result .= $text; // actual output value
				}
				
				$first_col = false;
			}
			
			if ( $found_values ) $result .= ')';
		}
		
		$result .= $rowend;
	}
	
	protected function showFurtherResults( &$result, $res, $rowstart, $rowend ) {
		$link = $res->getQueryLink();
		
		if ( $this->getSearchLabel( SMW_OUTPUT_WIKI ) ) {
			$link->setCaption( $this->getSearchLabel( SMW_OUTPUT_WIKI ) );
		}
		
		if ( $this->mSep != '' ) {
			$link->setParameter( $this->mSep, 'sep' );
		}
		
		$link->setParameter( $this->mFormat, 'format' );

		if ( $this->mTemplate != '' ) {
			$link->setParameter( $this->mTemplate, 'template' );
			if ( array_key_exists( 'link', $this->m_params ) ) { // linking may interfere with templates
				$link->setParameter( $this->m_params['link'], 'link' );
			}
		}
		
		if ( $this->mUserParam != '' ) {
			$link->setParameter( $this->mUserParam, 'userparam' );
		}
		
		if ( $this->mColumns != '' ) {
			$link->setParameter( $this->mColumns, 'columns' );
		}
		
		if ( $this->mIntro != '' ) {
			$link->setParameter( $this->mIntro, 'intro' );
		}
		
		if ( $this->mOutro != '' ) {
			$link->setParameter( $this->mOutro, 'outro' );
		}
		
		if ( $this->mIntroTemplate != '' ) {
			$link->setParameter( $this->mIntroTemplate, 'introtemplate' );
		}
		
		if ( $this->mOutroTemplate != '' ) {
			$link->setParameter( $this->mOutroTemplate, 'outrotemplate' );
		}
		
		$result .= $rowstart . ' '. $link->getText( SMW_OUTPUT_WIKI, $this->mLinker ) . $rowend;
	}

	public function getParameters() {
		$params = array_merge( parent::getParameters(), parent::textDisplayParameters() );
		
		$plainlist = ( $this->mFormat != 'ul' && $this->mFormat != 'ol' );
		
		if ( $plainlist ) {
			$params['sep'] = new Parameter( 'sep' );
			$params['sep']->setMessage( 'smw_paramdesc_sep' );
			$params['sep']->setDefault( '' );
		}
		
		$params['template'] = new Parameter( 'template' );
		$params['template']->setMessage( 'smw_paramdesc_template' );
		$params['template']->setDefault( '' );	
		
		if ( !$plainlist ) {
			$params['columns'] = new Parameter( 'columns', Parameter::TYPE_INTEGER );
			$params['columns']->setMessage( 'smw_paramdesc_columns', 1 );
			$params['columns']->setDefault( '', false );		
		}
		
		$params['userparam'] = new Parameter( 'userparam' );
		$params['userparam']->setMessage( 'smw_paramdesc_userparam' );		
		$params['userparam']->setDefault( '' );
		
		$params['introtemplate'] = new Parameter( 'introtemplate' );
		$params['introtemplate']->setMessage( 'smw_paramdesc_introtemplate' );		
		$params['introtemplate']->setDefault( '' );
		
		$params['outrotemplate'] = new Parameter( 'outrotemplate' );
		$params['outrotemplate']->setMessage( 'smw_paramdesc_outrotemplate' );		
		$params['outrotemplate']->setDefault( '' );
		
		return $params;
	}

}
