<?php

namespace SMW;

use SMWQueryResult;
use SMWQuery;
use SMWQueryProcessor;
use SMW\Query\PrintRequest;
use SMWExporter;
use SMWTurtleSerializer;
use SMWRDFXMLSerializer;

/**
 * Printer class for generating RDF output
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class RdfResultPrinter extends FileExportPrinter {

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

	protected function getResultText( SMWQueryResult $res, $outputMode ) {
		if ( $outputMode == SMW_OUTPUT_FILE ) { // make RDF file
			$serializer = $this->syntax == 'turtle' ? new SMWTurtleSerializer() : new SMWRDFXMLSerializer();
			$serializer->startSerialization();
			$serializer->serializeExpData( SMWExporter::getInstance()->getOntologyExpData( '' ) );

			while ( $row = $res->getNext() ) {
				$subjectDi = reset( $row )->getResultSubject();
				$data = SMWExporter::getInstance()->makeExportDataForSubject( $subjectDi );

				foreach ( $row as $resultarray ) {
					$printreq = $resultarray->getPrintRequest();
					$property = null;

					switch ( $printreq->getMode() ) {
						case PrintRequest::PRINT_PROP:
							$property = $printreq->getData()->getDataItem();
						break;
						case PrintRequest::PRINT_CATS:
							$property = new SMWDIProperty( '_TYPE' );
						break;
						case PrintRequest::PRINT_CCAT:
							// not serialised right now
						break;
						case PrintRequest::PRINT_THIS:
							// ignored here (object is always included in export)
						break;
					}

					if ( !is_null( $property ) ) {
						SMWExporter::getInstance()->addPropertyValues( $property, $resultarray->getContent(), $data, $subjectDi );
					}
				}
				$serializer->serializeExpData( $data );
			}

			$serializer->finishSerialization();

			return $serializer->flushContent();
		} else { // just make link to feed
			$this->isHTML = ( $outputMode == SMW_OUTPUT_HTML ); // yes, our code can be viewed as HTML if requested, no more parsing needed

			return $this->getLink( $res, $outputMode )->getText( $outputMode, $this->mLinker );
		}
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param ParamDefinition[] $definitions
	 *
	 * @return array
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
