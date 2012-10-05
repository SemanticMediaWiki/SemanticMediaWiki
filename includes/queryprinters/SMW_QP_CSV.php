<?php
/**
 * CSV export for SMW Queries
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer class for generating CSV output
 * 
 * @author Nathan R. Yergler
 * @author Markus Krötzsch
 * 
 * @ingroup SMWQuery
 */
class SMWCsvResultPrinter extends SMWExportPrinter {
	
	protected $m_sep;

	/**
	 * @see SMWResultPrinter::handleParameters
	 * 
	 * @since 1.7
	 *
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );
		
		$this->m_sep = str_replace( '_', ' ', $this->params['sep'] );
	}

	/**
	 * @see SMWIExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string
	 */
	public function getMimeType( SMWQueryResult $queryResult ) {
		return 'text/csv';
	}

	/**
	 * @see SMWIExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string|boolean
	 */
	public function getFileName( SMWQueryResult $queryResult ) {
		return 'result.csv';
	}

	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	public function getName() {
		return wfMessage( 'smw_printername_csv' )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		$result = '';
		
		if ( $outputmode == SMW_OUTPUT_FILE ) { // make CSV file
			$csv = fopen( 'php://temp', 'r+' );
			$sep = str_replace( '_', ' ', $this->params['sep'] );

			if ( $this->params['showsep'] ) {
				fputs( $csv, "sep=" . $sep . "\n" );
			}

			if ( $this->mShowHeaders ) {
				$header_items = array();
				
				foreach ( $res->getPrintRequests() as $pr ) {
					$header_items[] = $pr->getLabel();
				}
				
				fputcsv( $csv, $header_items, $sep );
			}
			
			while ( $row = $res->getNext() ) {
				$row_items = array();
				
				foreach ( $row as /* SMWResultArray */ $field ) {
					$growing = array();
					
					while ( ( $object = $field->getNextDataValue() ) !== false ) {
						$growing[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
					} 
					
					$row_items[] = implode( ',', $growing );
				}
				
				fputcsv( $csv, $row_items, $this->m_sep );
			}

			rewind( $csv );
			$result .= stream_get_contents( $csv );
		} else { // just make link to feed
			$result .= $this->getLink( $res, $outputmode )->getText( $outputmode, $this->mLinker );
			$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
		}
		return $result;
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$definitions['searchlabel']->setDefault( wfMessage( 'smw_csv_link' )->inContentLanguage()->text() );

		$definitions['limit']->setDefault( 100 );

		$params[] = array(
			'name' => 'sep',
			'message' => 'smw-paramdesc-csv-sep',
			'default' => ',',
		);

		$params['showsep'] = array(
			'type' => 'boolean',
			'default' => false,
			'message' => 'smw-paramdesc-showsep',
		);
		return $params;
	}

}
