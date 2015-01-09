<?php

namespace SMW\SPARQLStore;

use SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException;
use SMW\SPARQLStore\QueryEngine\RawResultParser;
use SMW\SPARQLStore\QueryEngine\FederateResultSet;

/**
 * @see https://jena.apache.org/documentation/serving_data/index.html
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FusekiHttpDatabaseConnector extends GenericHttpDatabaseConnector {

	/**
	 * @see GenericHttpDatabaseConnector::doQuery
	 */
	public function doQuery( $sparql ) {

		if ( $this->m_queryEndpoint === '' ) {
			throw new BadHttpDatabaseResponseException( BadHttpDatabaseResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->m_queryEndpoint );

		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array(
			'Accept: application/sparql-results+xml,application/xml;q=0.8',
			'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
		) );

		$this->httpRequest->setOption( CURLOPT_POST, true );

		$parameterString = "query=" . urlencode( $sparql ) .
			( ( $this->m_defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $this->m_defaultGraph ) : '' ) . '&output=xml';

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );

		$xmlResult = $this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			$rawResultParser = new RawResultParser();
			return $rawResultParser->parse( $xmlResult );
		}

		$this->mapHttpRequestError( $this->m_queryEndpoint, $sparql );

		return new FederateResultSet(
			array(),
			array(),
			array(),
			FederateResultSet::ERROR_UNREACHABLE
		);
	}

}
