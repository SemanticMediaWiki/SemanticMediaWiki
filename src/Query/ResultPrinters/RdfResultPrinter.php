<?php

namespace SMW\Query\ResultPrinters;

use SMW\Query\PrintRequest;
use SMW\DIProperty;
use SMWExporter as Exporter;
use SMWQueryResult as QueryResult;
use SMWRDFXMLSerializer;
use SMWTurtleSerializer;

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
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return wfMessage( 'smw_printername_rdf' )->text();
	}

	/**
	 * @see FileExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getMimeType( QueryResult $queryResult ) {
		return $this->params['syntax'] === 'turtle' ? 'application/x-turtle' : 'application/xml';
	}

	/**
	 * @see FileExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFileName( QueryResult $queryResult ) {
		return $this->params['syntax'] === 'turtle' ? 'result.ttl' : 'result.rdf';
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions['limit']->setDefault( 100 );

		$definitions['searchlabel']->setDefault( wfMessage( 'smw_rdf_link' )->inContentLanguage()->text() );

		$definitions[] = [
			'name' => 'syntax',
			'message' => 'smw-paramdesc-rdfsyntax',
			'values' => [ 'rdfxml', 'turtle' ],
			'default' => 'rdfxml',
		];

		return $definitions;
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {

		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getRdfLink( $res, $outputMode );
		}

		return $this->makeExport( $res, $outputMode );
	}

	private function getRdfLink( QueryResult $res, $outputMode ) {

		// Can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = $outputMode == SMW_OUTPUT_HTML;

		$link = $this->getLink(
			$res,
			$outputMode
		);

		return $link->getText( $outputMode, $this->mLinker );
	}

	private function makeExport( QueryResult $res, $outputMode ) {

		$exporter = Exporter::getInstance();
		$serializer = $exporter->newExportSerializer( $this->params['syntax'] );

		$serializer->startSerialization();
		$serializer->serializeExpData( $exporter->newOntologyExpData( '' ) );

		while ( $row = $res->getNext() ) {
			$serializer->serializeExpData( $this->makeExportData( $exporter, $row ) );
		}

		$serializer->finishSerialization();

		return $serializer->flushContent();
	}

	private function makeExportData( $exporter, $row ) {

		$subject = reset( $row )->getResultSubject();
		$expData = $exporter->makeExportDataForSubject( $subject );

		foreach ( $row as $resultarray ) {
			$printRequest = $resultarray->getPrintRequest();
			$property = null;

			switch ( $printRequest->getMode() ) {
				case PrintRequest::PRINT_PROP:
					$property = $printRequest->getData()->getDataItem();
				break;
				case PrintRequest::PRINT_CATS:
					$property = new DIProperty( '_TYPE' );
				break;
				case PrintRequest::PRINT_CCAT:
					// not serialised right now
				break;
				case PrintRequest::PRINT_THIS:
					// ignored here (object is always included in export)
				break;
			}

			if ( $property !== null ) {
				$exporter->addPropertyValues( $property, $resultarray->getContent(), $expData );
			}
		}

		return $expData;
	}

}
