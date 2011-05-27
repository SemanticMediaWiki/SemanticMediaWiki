<?php
/**
 * Print query results in tables.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * New implementation of SMW's printer for result tables.
 *
 * @ingroup SMWQuery
 */
class SMWTableResultPrinter extends SMWResultPrinter {

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_' . $this->mFormat );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		global $smwgIQRunningNumber;
		SMWOutputs::requireHeadItem( SMW_HEADER_SORTTABLE );

		// print header
		$result = '<table class="smwtable"' .
			  ( $this->mFormat == 'broadtable' ? ' width="100%"' : '' ) .
				  " id=\"querytable$smwgIQRunningNumber\">\n";
			  
		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) { // building headers
			$result .= "\t<tr>\n";
			
			foreach ( $res->getPrintRequests() as $pr ) {
				$result .= "\t\t<th>" . $pr->getText( $outputmode, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null:$this->mLinker ) ) . "</th>\n";
			}
			
			$result .= "\t</tr>\n";
		}

		// print all result rows
		while ( $row = $res->getNext() ) {
			$result .= "\t<tr>\n";
			$firstcol = true;
			$fieldcount = - 1;
			foreach ( $row as $field ) {
				$fieldcount = $fieldcount + 1;

				$result .= "\t\t<td";
				$alignment = trim( $field->getPrintRequest()->getParameter( 'align' ) );
				if ( ( $alignment == 'right' ) || ( $alignment == 'left' ) || ( $alignment == 'center' ) ) {
					$result .= ' style="text-align:' . $alignment . ';"';
				}
				$result .= ">";

				$first = true;
				while ( ( $dv = $field->getNextDataValue() ) !== false ) {
					if ( $first ) {
						$sortkey = $dv->getDataItem()->getSortKey();
						if ( is_numeric( $sortkey ) ) { // additional hidden sortkey for numeric entries
							$result .= '<span class="smwsortkey">' . $sortkey . '</span>';
						}
						$first = false;
					} else {
						$result .= '<br />';
					}
					// use shorter "LongText" for wikipage
					$result .= ( ( $dv->getTypeID() == '_wpg' ) || ( $dv->getTypeID() == '__sin' ) ) ?
						   $dv->getLongText( $outputmode, $this->getLinker( $firstcol ) ) :
						   $dv->getShortText( $outputmode, $this->getLinker( $firstcol ) );
				}
				$result .= "</td>\n";
				$firstcol = false;
			}
			$result .= "\t</tr>\n";
		}

		// print further results footer
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $res->getQueryLink();
			if ( $this->getSearchLabel( $outputmode ) ) {
				$link->setCaption( $this->getSearchLabel( $outputmode ) );
			}
			$result .= "\t<tr class=\"smwfooter\"><td class=\"sortbottom\" colspan=\"" . $res->getColumnCount() . '"> ' . $link->getText( $outputmode, $this->mLinker ) . "</td></tr>\n";
		}
		$result .= "</table>\n"; // print footer
		$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
		return $result;
	}

	public function getParameters() {
		return array_merge( parent::getParameters(), parent::textDisplayParameters() );
	}
	
}
