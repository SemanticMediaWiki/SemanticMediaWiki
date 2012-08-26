<?php
/**
 * RDF export for SMW Queries
 * @file
 * @ingroup SMWQuery
 */

/**
 * Printer class for generating RDF output
 * 
 * @since 1.6
 * 
 * @author Markus Krötzsch
 * 
 * @ingroup SMWQuery
 */
class SMWRDFResultPrinter extends SMWExportPrinter {
	
	/**
	 * The syntax to be used for export. May be 'rdfxml' or 'turtle'.
	 */
	protected $syntax;

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
		$this->syntax = $params['syntax'];
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
		return $this->syntax == 'turtle' ? 'application/x-turtle' : 'application/xml';
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
		return $this->syntax == 'turtle' ? 'result.ttl' : 'result.rdf';
	}

	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	public function getName() {
		return wfMessage( 'smw_printername_rdf' )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		if ( $outputmode == SMW_OUTPUT_FILE ) { // make RDF file
			$serializer = $this->syntax == 'turtle' ? new SMWTurtleSerializer() : new SMWRDFXMLSerializer();
			$serializer->startSerialization();
			$serializer->serializeExpData( SMWExporter::getOntologyExpData( '' ) );
			
			while ( $row = $res->getNext() ) {
				$subjectDi = reset( $row )->getResultSubject();
				$data = SMWExporter::makeExportDataForSubject( $subjectDi );
				
				foreach ( $row as $resultarray ) {
					$printreq = $resultarray->getPrintRequest();
					$property = null;
					
					switch ( $printreq->getMode() ) {
						case SMWPrintRequest::PRINT_PROP:
							$property = $printreq->getData()->getDataItem();
						break;
						case SMWPrintRequest::PRINT_CATS:
							$property = new SMWDIProperty( '_TYPE' );
						break;
						case SMWPrintRequest::PRINT_CCAT:
							// not serialised right now
						break;
						case SMWPrintRequest::PRINT_THIS:
							// ignored here (object is always included in export)
						break;
					}
					
					if ( !is_null( $property ) ) {
						SMWExporter::addPropertyValues( $property, $resultarray->getContent() , $data, $subjectDi );
					}					
				}
				$serializer->serializeExpData( $data );
			}
			
			$serializer->finishSerialization();
			
			return $serializer->flushContent();
		} else { // just make link to feed
			$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
			
			return $this->getLink( $res, $outputmode )->getText( $outputmode, $this->mLinker );
		}
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
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions['limit']->setDefault( 100 );

		$definitions['searchlabel']->setDefault( wfMessage( 'smw_rdf_link' )->inContentLanguage()->text() );

		$definitions[] = array(
			'name' => 'syntax',
			'message' => 'smw-paramdesc-rdfsyntax',
			'values' => array( 'rdfxml', 'turtle' ),
			'default' => 'rdfxml',
		);

		return $definitions;
	}

}
