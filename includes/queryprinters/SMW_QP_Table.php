<?php

/**
 * Print query results in tables.
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * 
 * @file
 * @ingroup SMWQuery
 */
class SMWTableResultPrinter extends SMWResultPrinter {

	protected $mHTMLClass = '';

	public function getName() {
		return wfMsg( 'smw_printername_' . $this->mFormat );
	}

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
		$this->mHTMLClass = $params['class'];
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		$result = '';
		
		$columnClasses = array();
		
		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) { // building headers
			$headers = array();
			
			foreach ( $res->getPrintRequests() as /* SMWPrintRequest */ $pr ) {
				$attribs = array();
				$columnClass = str_replace( array( ' ', '_' ), '-', $pr->getText( SMW_OUTPUT_WIKI ) );
				$attribs['class'] = $columnClass;
				// Also add this to the array of classes, for
				// use in displaying each row.
				$columnClasses[] = $columnClass;
				$text = $pr->getText( $outputmode, ( $this->mShowHeaders == SMW_HEADERS_PLAIN ? null : $this->mLinker ) );
				
				$headers[] = Html::rawElement(
					'th',
					$attribs,
					$text === '' ? '&nbsp;' : $text
				);
			}
			
			$headers = '<tr>' . implode( "\n", $headers ) . '</tr>';
			
			if ( $outputmode == SMW_OUTPUT_HTML ) {
				$headers = '<thead>' . $headers . '</thead>'; 
			}
			$headers = "\n$headers\n";

			$result .= $headers;
		}
		
		$tableRows = array();
		$rowNum = 1;
		while ( $subject = $res->getNext() ) {
			$tableRows[] = $this->getRowForSubject( $subject, $outputmode, $columnClasses, $rowNum++ );
		}

		$tableRows = implode( "\n", $tableRows );
		
		if ( $outputmode == SMW_OUTPUT_HTML ) {
			$tableRows = '<tbody>' . $tableRows . '</tbody>'; 
		}
		
		$result .= $tableRows;
		
		// print further results footer
		if ( $this->linkFurtherResults( $res ) ) {
			$link = $res->getQueryLink();
			if ( $this->getSearchLabel( $outputmode ) ) {
				$link->setCaption( $this->getSearchLabel( $outputmode ) );
			}
			$result .= "\t<tr class=\"smwfooter\"><td class=\"sortbottom\" colspan=\"" . $res->getColumnCount() . '"> ' . $link->getText( $outputmode, $this->mLinker ) . "</td></tr>\n";
		}
		
		// Put the <table> tag around the whole thing
		$tableAttrs = array( 'class' => $this->mHTMLClass );
		
		if ( $this->mFormat == 'broadtable' ) {
			$tableAttrs['width'] = '100%';
		}
		
		$result = Xml::tags( 'table', $tableAttrs, $result );

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
	protected function getRowForSubject( array /* of SMWResultArray */ $subject, $outputmode, $columnClasses, $rowNum ) {
		$cells = array();
		
		foreach ( $subject as $i => $field ) {
			// $columnClasses will be empty if "headers=hide"
			// was set.
			if ( array_key_exists( $i, $columnClasses ) ) {
				$columnClass = $columnClasses[$i];
			} else {
				$columnClass = null;
			}
			$cells[] = $this->getCellForPropVals( $field, $outputmode, $columnClass );
		}
		
		$rowClass = ( $rowNum % 2 == 1 ) ? 'row-odd' : 'row-even';
		return "<tr class=\"$rowClass\">\n\t" . implode( "\n\t", $cells ) . "\n</tr>";
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
			
			if ( is_numeric( $sortkey ) ) {
				$attribs['data-sort-value'] = $sortkey;
			}
			
			$alignment = trim( $resultArray->getPrintRequest()->getParameter( 'align' ) );
		
			if ( in_array( $alignment, array( 'right', 'left', 'center' ) ) ) {
				$attribs['style'] = "text-align:' . $alignment . ';";
			}
			$attribs['class'] = $columnClass;

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
			$value = $dv->getShortText( $outputmode, $this->getLinker( $isSubject ) );
			$values[] = $value;
		}
		
		return implode( '<br />', $values );
	}
	
	public function getParameters() {
		$params = array_merge( parent::getParameters(), parent::textDisplayParameters() );
		
		$params['class'] = new Parameter( 'class', Parameter::TYPE_STRING );
		$params['class']->setMessage( 'smw-paramdesc-table-class' );
		$params['class']->setDefault( 'sortable wikitable smwtable' );
		
		return $params;
	}
	
}
