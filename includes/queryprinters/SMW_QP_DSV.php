<?php

/**
 * Result printer to print results in UNIX-style DSV (deliminter separated value) format.
 * 
 * @file SMW_QP_DSV.php
 * @ingroup SMWQuery
 * @since 1.6
 *
 * @licence GNU GPL v3
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * Based on the SMWCsvResultPrinter class.
 */
class SMWDSVResultPrinter extends SMWResultPrinter {
	
	protected $separator = ':';
	protected $fileName = 'result.dsv';
	
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
		
		// Do not allow backspaces as delimiter, as they'll break stuff.
		if ( trim( $params['separator'] ) != '\\' ) {
			$this->separator = trim( $params['separator'] );
		}
		
		$this->fileName = str_replace( ' ', '_', $params['filename'] );
	}	

	public function getMimeType( $res ) {
		return 'text/dsv';
	}

	public function getFileName( $res ) {
		return $this->fileName;
	}

	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	public function getName() {
		return wfMsg( 'smw_printername_dsv' );
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		if ( $outputmode == SMW_OUTPUT_FILE ) { // Make the DSV file.
			return $this->getResultFileContents( $res );
		}
		else { // Create a link pointing to the DSV file.
			return $this->getLinkToFile( $res, $outputmode );
		}
	}
	
	/**
	 * Returns the query result in DSV.
	 * 
	 * @since 1.6
	 *  
	 * @param SMWQueryResult $res
	 * 
	 * @return string
	 */	
	protected function getResultFileContents( SMWQueryResult $res ) {
		$lines = array();
		
		if ( $this->mShowHeaders ) {
			$headerItems = array();
			
			foreach ( $res->getPrintRequests() as $pr ) {
				$headerItems[] = $pr->getLabel();
			}
			
			$lines[] = $this->getDSVLine( $headerItems );
		}
		
		// Loop over the result objects (pages).
		while ( $row = $res->getNext() ) {
			$rowItems = array();
			
			// Loop over their fields (properties).
			foreach ( $row as /* SMWResultArray */ $field ) {
				$itemSegments = array();
				
				// Loop over all values for the property.
				while ( ( $object = $field->getNextDataValue() ) !== false ) {
					$itemSegments[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
				} 
				
				// Join all values into a single string, separating them with comma's.
				$rowItems[] = implode( ',', $itemSegments );
			}
			
			$lines[] = $this->getDSVLine( $rowItems );
		}

		return implode( "\n", $lines );	
	}
	
	/**
	 * Returns a single DSV line.
	 * 
	 * @since 1.6
	 *  
	 * @param array $fields
	 * 
	 * @return string
	 */		
	protected function getDSVLine( array $fields ) {
		return implode( $this->separator, array_map( array( $this, 'encodeDSV' ), $fields ) );
	}
	
	/**
	 * Encodes a single DSV.
	 * 
	 * @since 1.6
	 *  
	 * @param string $value
	 * 
	 * @return string
	 */
	protected function encodeDSV( $value ) {
		// TODO
		// \nnn or \onnn or \0nnn for the character with octal value nnn
		// \xnn for the character with hexadecimal value nn
		// \dnnn for the character with decimal value nnn
		// \unnnn for a hexadecimal Unicode literal.
		return str_replace(
			array( '\n', '\r', '\t', '\b', '\f', '\\', $this->separator ),
			array( "\n", "\r", "\t", "\b", "\f", '\\\\', "\\$this->separator" ),
			$value
		);
	}
	
	/**
	 * Returns html for a link to a query that returns the DSV file.
	 * 
	 * @since 1.6
	 *  
	 * @param SMWQueryResult $res
	 * @param $outputmode
	 * 
	 * @return string
	 */		
	protected function getLinkToFile( SMWQueryResult $res, $outputmode ) {
		if ( $this->getSearchLabel( $outputmode ) ) {
			$label = $this->getSearchLabel( $outputmode );
		} else {
			$label = wfMsgForContent( 'smw_dsv_link' );
		}

		$link = $res->getQueryLink( $label );
		$link->setParameter( 'dsv', 'format' );
		$link->setParameter( $this->separator, 'separator' );
		$link->setParameter( $this->fileName, 'filename' );
		
		if ( array_key_exists( 'mainlabel', $this->m_params ) && $this->m_params['mainlabel'] !== false ) {
			$link->setParameter( $this->m_params['mainlabel'], 'mainlabel' );
		}
		
		$link->setParameter( $this->mShowHeaders ? 'show' : 'hide', 'headers' );
			
		if ( array_key_exists( 'limit', $this->m_params ) ) {
			$link->setParameter( $this->m_params['limit'], 'limit' );
		} else { // Use a reasonable default limit
			$link->setParameter( 100, 'limit' );
		}

		// yes, our code can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); 
		return $link->getText( $outputmode, $this->mLinker );
	}

	public function getParameters() {
		$params = array_merge( parent::getParameters(), $this->exportFormatParameters() );
		
		$params['separator'] = new Parameter( 'separator', 'sep' );
		$params['separator']->setMessage( 'smw-paramdesc-dsv-separator' );
		$params['separator']->setDefault( $this->separator );
		
		$params['filename'] = new Parameter( 'filename' );
		$params['filename']->setMessage( 'smw-paramdesc-dsv-filename' );
		$params['filename']->setDefault( $this->fileName );
		
		return $params;
	}

}
