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

	/**
	 * List of printrequests for which numeric sort keys are used.
	 * print request hash => true
	 * 
	 * @since 1.6.1
	 * 
	 * @var array
	 */
	protected $columnsWithSortKey = array();
	
	public function getName() {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		return wfMsg( 'smw_printername_' . $this->mFormat );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		global $smwgIQRunningNumber;
		SMWOutputs::requireHeadItem( SMW_HEADER_SORTTABLE );

		$tableRows = array();
		
		while ( $subject = $res->getNext() ) {
			$tableRows[] = $this->getRowForSubject( $subject, $outputmode );
		}
		
		// print header
		$result = '<table class="smwtable"' .
			  ( $this->mFormat == 'broadtable' ? ' width="100%"' : '' ) .
				  " id=\"querytable$smwgIQRunningNumber\">\n";
			  
		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) { // building headers
			$headers = array();
			
			foreach ( $res->getPrintRequests() as $pr ) {
				$attribs = array();
				
				if ( array_key_exists( $pr->getHash(), $this->columnsWithSortKey ) ) {
					$attribs['class'] = 'numericsort';
				}
				
				$headers[] = Html::rawElement(
					'th',
					$attribs,
					$pr->getText( $outputmode, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null:$this->mLinker ) )
				);
			}
			
			array_unshift( $tableRows, '<tr>' . implode( "\n", $headers ) . '</tr>' );
		}

		$result .= implode( "\n", $tableRows );
		
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
		$attribs = array();
		
		$alignment = trim( $resultArray->getPrintRequest()->getParameter( 'align' ) );
		
		if ( in_array( $alignment, array( 'right', 'left', 'center' ) ) ) {
			$attribs['style'] = "text-align:' . $alignment . ';";
		}
		
		return Html::rawElement(
			'td',
			$attribs,
			$this->getCellContent( $resultArray, $outputmode )
		);
	}
	
	/**
	 * Gets the contents for a table cell for all values of a property of a subject.
	 * 
	 * @since 1.6.1
	 * 
	 * @param SMWResultArray $resultArray
	 * @param $outputmode
	 * 
	 * @return string
	 */
	protected function getCellContent( SMWResultArray $resultArray, $outputmode ) {
		$values = array();
		$isFirst = true;
		
		while ( ( $dv = $resultArray->getNextDataValue() ) !== false ) {
			$sortKey = '';
			
			if ( $isFirst ) {
				$isFirst = false;
				$sortkey = $dv->getDataItem()->getSortKey();
				
				if ( is_numeric( $sortkey ) ) { // additional hidden sortkey for numeric entries
					$this->columnsWithSortKey[$resultArray->getPrintRequest()->getHash()] = true;
					$sortKey .= '<span class="smwsortkey">' . $sortkey . '</span>';
				}
			}
			
			$isSubject = $resultArray->getPrintRequest()->getMode() == SMWPrintRequest::PRINT_THIS;
			$value = ( ( $dv->getTypeID() == '_wpg' ) || ( $dv->getTypeID() == '__sin' ) ) ?
				   $dv->getLongText( $outputmode, $this->getLinker( $isSubject ) ) :
				   $dv->getShortText( $outputmode, $this->getLinker( $isSubject ) );
			
			$values[] = $sortKey . $value;
		}
		
		return implode( '<br />', $values );
	}
	
	public function getParameters() {
		return array_merge( parent::getParameters(), parent::textDisplayParameters() );
	}
	
}
