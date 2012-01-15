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
class SMWRDFResultPrinter extends SMWResultPrinter {
	
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

	public function getMimeType( $res ) {
		return $this->syntax == 'turtle' ? 'application/x-turtle' : 'application/xml';
	}

	public function getFileName( $res ) {
		return $this->syntax == 'turtle' ? 'result.ttl' : 'result.rdf';
	}

	public function getQueryMode( $context ) {
		return ( $context == SMWQueryProcessor::SPECIAL_PAGE ) ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	public function getName() {
		return wfMsg( 'smw_printername_rdf' );
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
			if ( $this->getSearchLabel( $outputmode ) ) {
				$label = $this->getSearchLabel( $outputmode );
			} else {
				$label = wfMsgForContent( 'smw_rdf_link' );
			}

			$link = $res->getQueryLink( $label );
			$link->setParameter( 'rdf', 'format' );
			$link->setParameter( $this->syntax, 'syntax' );
			
			if ( array_key_exists( 'limit', $this->params ) ) {
				$link->setParameter( $this->params['limit'], 'limit' );
			} else { // use a reasonable default limit
				$link->setParameter( 100, 'limit' );
			}
			
			$this->isHTML = ( $outputmode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed
			
			return $link->getText( $outputmode, $this->mLinker );
		}
	}

	public function getParameters() {
		$params = array();
		
		$params['syntax'] = new Parameter( 'syntax' );
		$params['syntax']->setMessage( 'smw_paramdesc_rdfsyntax' );
		$params['syntax']->addCriteria( new CriterionInArray( 'rdfxml', 'turtle' ) );
		$params['syntax']->setDefault( 'rdfxml' );
		
		return array_merge( parent::getParameters(), $this->exportFormatParameters(), $params );
	}

}
