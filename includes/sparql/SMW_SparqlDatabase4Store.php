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
	 * Execute a SPARQL query and return an SMWSparqlResultWrapper object
	 * that contains the results. Compared to SMWSparqlDatabase::doQuery(),
	 * this also supports the parameter "restricted=1" which 4Store provides
	 * to enforce strict resource bounds on query answering. The method also
	 * checks if these bounds have been met, and records this in the query
	 * result.
	 *
	 * @note The restricted option in 4Store mainly enforces the given soft
	 * limit more strictly. To disable/configure it, simply change the soft
	 * limit settings of your 4Store server.
	 *
	 * @param $sparql string with the complete SPARQL query (SELECT or ASK)
	 * @return SMWSparqlResultWrapper
	 */
	public function doQuery( $sparql ) {
		//$result = parent::doQuery( $sparql );
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_queryEndpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_POST, true );
		$parameterString = "query=" . urlencode( $sparql ) . "&restricted=1" .
			( ( $this->m_defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $this->m_defaultGraph ) : '' );
		curl_setopt( $this->m_curlhandle, CURLOPT_POSTFIELDS, $parameterString );
		$xmlResult = curl_exec( $this->m_curlhandle );

		if ( curl_errno( $this->m_curlhandle ) == 0 ) {
			$xmlParser = new SMWSparqlResultParser();
			$result = $xmlParser->makeResultFromXml( $xmlResult );
		} else {
			$this->throwSparqlErrors( $this->m_updateEndpoint, $sparql );
			$result = new SMWSparqlResultWrapper( array(), array(), array(), SMWSparqlResultWrapper::ERROR_UNREACHABLE );
		}

		foreach ( $result->getComments() as $comment ) {
			if ( strpos( $comment, 'warning: hit complexity limit' ) === 0 ||
			     strpos( $comment, 'some results have been dropped' ) === 0 ) {
				$result->setErrorCode( SMWSparqlResultWrapper::ERROR_INCOMPLETE );
			} //else debug_zval_dump($comment);
		}
		return $result;
	}

	/**
	 * Complex SPARQL Update delete operations are not supported in 4Store
	 * as of v1.1.3, hence this implementation uses a less efficient method
	 * for accomplishing this.
	 *
	 * @param $propertyName string Turtle name of marking property
	 * @param $objectName string Turtle name of marking object/value
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 * @return boolean stating whether the operations succeeded
	 */
	public function deleteContentByValue( $propertyName, $objectName, $extraNamespaces = array() ) {
		$affectedObjects = $this->select( '*', "?s $propertyName $objectName", array(), $extraNamespaces );
		$success = ( $affectedObjects->getErrorCode() == SMWSparqlResultWrapper::ERROR_NOERROR );
		foreach ( $affectedObjects as $expElements ) {
			if ( count( $expElements ) > 0 ) {
				$turtleName = SMWTurtleSerializer::getTurtleNameForExpElement( reset( $expElements ) );
				$success = $this->delete( "$turtleName ?p ?o", "$turtleName ?p ?o", $extraNamespaces ) && $success;
			}
		}
		return $success;
	}

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
		if ( $this->m_dataEndpoint === '' ) {
			throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_NOSERVICE, "SPARQL POST with data: $payload", 'not specified' );
		}
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_dataEndpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_POST, true );
		$parameterString = "data=" . urlencode( $payload ) . '&graph=' .
			( ( $this->m_defaultGraph !== '' )? urlencode( $this->m_defaultGraph ) : 'default' ) .
			'&mime-type=application/x-turtle';
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