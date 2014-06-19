<?php

namespace SMW\SPARQLStore;

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
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_queryEndpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_HTTPHEADER, array('Accept: application/sparql-results+xml,application/xml;q=0.8' ));
		curl_setopt( $this->m_curlhandle, CURLOPT_POST, true );
		$parameterString = "query=" . urlencode( $sparql ) .
			( ( $this->m_defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $this->m_defaultGraph ) : '' ) . '&output=xml';
		curl_setopt( $this->m_curlhandle, CURLOPT_POSTFIELDS, $parameterString );
		curl_setopt( $this->m_curlhandle, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded;charset=UTF-8'));

		$xmlResult = curl_exec( $this->m_curlhandle );

		if ( curl_errno( $this->m_curlhandle ) == 0 ) {
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
