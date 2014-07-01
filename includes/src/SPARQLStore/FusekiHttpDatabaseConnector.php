<?php

namespace SMW\SPARQLStore;

use SMW\SPARQLStore\BadHttpDatabaseResponseException as SMWSparqlDatabaseError;
use SMWSparqlDatabase as SparqlDatabase;
use SMWSparqlResultParser as SparqlResultParser;
use SMWSparqlResultWrapper as SparqlResultWrapper;

/**
 * @see https://jena.apache.org/documentation/serving_data/index.html
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class FusekiHttpDatabaseConnector extends SparqlDatabase {

	/**
	 * @see SparqlDatabase::doQuery
	 */
	public function doQuery( $sparql ) {

		if ( $this->m_queryEndpoint === '' ) {
			throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->m_queryEndpoint );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array('Accept: application/sparql-results+xml,application/xml;q=0.8' ) );
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$parameterString = "query=" . urlencode( $sparql ) .
			( ( $this->m_defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $this->m_defaultGraph ) : '' ) . '&output=xml';

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8') );

		$xmlResult = $this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			$xmlParser = new SparqlResultParser();
			return $xmlParser->makeResultFromXml( $xmlResult );
		}

		$this->throwSparqlErrors( $this->m_queryEndpoint, $sparql );

		return new SparqlResultWrapper(
			array(),
			array(),
			array(),
			SparqlResultWrapper::ERROR_UNREACHABLE
		);
	}

}
