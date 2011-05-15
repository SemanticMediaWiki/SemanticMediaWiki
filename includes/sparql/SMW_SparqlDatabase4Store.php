<?php
/**
 * 4Store specific adjustments for SMWSparqlDatabase
 * 
 * @file
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */

/**
 * Specific modifications of the SPARQL database implementation for 4Store.
 * 
 * @since 1.6
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */
class SMWSparqlDatabase4Store extends SMWSparqlDatabase {

	/**
	 * Execute a HTTP-based SPARQL POST request according to
	 * http://www.w3.org/2009/sparql/docs/http-rdf-update/.
	 * The method throws exceptions based on
	 * SMWSparqlDatabase::throwSparqlErrors(). If errors occur and this
	 * method does not throw anything, then an empty result with an error
	 * code is returned.
	 * 
	 * This method is specific to 4Store since it uses POST parameters that
	 * are not given in the specification.
	 *
	 * @param $payload string Turtle serialization of data to send
	 * @return SMWSparqlResultWrapper
	 */
	public function doHttpPost( $payload ) {
		if ( $this->m_dataEndpoint == '' ) {
			throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_NOSERVICE, "SPARQL POST with data: $payload", 'not specified', $error );
		}
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_dataEndpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_POST, true );
		$parameterString = "data=" . urlencode( $payload ) . '&graph=default&mime-type=application/x-turtle';
		curl_setopt( $this->m_curlhandle, CURLOPT_POSTFIELDS, $parameterString );

		curl_exec( $this->m_curlhandle );

		if ( curl_errno( $this->m_curlhandle ) == 0 ) {
			return true;
		} else { ///TODO The error reporting based on SPARQL (Update) is not adequate for the HTTP POST protocol
			$this->throwSparqlErrors( $this->m_dataEndpoint, $payload );
			return false;
		}
	}

}