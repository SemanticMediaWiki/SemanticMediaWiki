<?php

/**
 * Print query results in tables.
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw  < jeroendedauw@gmail.com >
 * 
 * @file
 * @ingroup SMWQuery
 */
class SMWTableResultPrinter extends SMWResultPrinter {

	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_' . $this->mFormat );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		global $smwgIQRunningNumber;

		$tableRows = array();
		
		while ( $subject = $res->getNext() ) {
			$tableRows[] = $this->getRowForSubject( $subject, $outputmode );
		}
		
		// print header
		$result = '<table class="sortable wikitable"' .
			  ( $this->mFormat == 'broadtable' ? ' width="100%"' : '' ) .
				  ">\n";
			  
		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) { // building headers
			$headers = array();
			
			foreach ( $res->getPrintRequests() as $pr ) {
				$attribs = array();
				
				$headers[] = Html::rawElement(
					'th',
					$attribs,
					$pr->getText( $outputmode, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null:$this->mLinker ) )
				);
			}
			
			array_unshift( $tableRows, '<thead><tr>' . implode( "\n", $headers ) . '</tr></thead><tbody>' );
		}

		$result .= implode( "\n", $tableRows ) . '</tbody>';
		
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
	protected function getRowForSubject( array /* of SMWResultArray */ $subject, $outputmode ) {
		$cells = array();
		
		foreach ( $subject as $field ) {
			$cells[] = $this->getCellForPropVals( $field, $outputmode );
		}
		
		return "<tr>\n\t" . implode( "\n\t", $cells ) . "\n</tr>";
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
	protected function getCellForPropVals( SMWResultArray $resultArray, $outputmode ) {
		$dataValues = array();
		
		while ( ( $dv = $resultArray->getNextDataValue() ) !== false ) {
			$dataValues[] = $dv;
		}
		
		$attribs = array();
		$content = null;
		
		if ( count( $dataValues ) > 0 ) {
			$sortkey = $dataValues[0]->getDataItem()->getSortKey();
			
			if ( is_numeric( $sortkey ) ) {
				$attribs['data-sort-value'] = $sortkey;
			}
			
			$alignment = trim( $resultArray->getPrintRequest()->getParameter( 'align' ) );
		
			if ( in_array( $alignment, array( 'right', 'left', 'center' ) ) ) {
				$attribs['style'] = "text-align:' . $alignment . ';";
			}
			
			$content = $this->getCellContent(
				$dataValues,
				$outputmode,
				$resultArray->getPrintRequest()->getMode() == SMWPrintRequest::PRINT_THIS
			);
		}
		
		return Html::rawElement(
			'td',
			$attribs,
			$content
		);
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
			$value = ( ( $dv->getTypeID() == '_wpg' ) || ( $dv->getTypeID() == '__sin' ) ) ?
				   $dv->getLongText( $outputmode, $this->getLinker( $isSubject ) ) :
				   $dv->getShortText( $outputmode, $this->getLinker( $isSubject ) );
			
			$values[] = $value;
		}
		
		return implode( '<br />', $values );
	}
	
	public function getParameters() {
		return array_merge( parent::getParameters(), parent::textDisplayParameters() );
	}
	
}
