<?php

namespace SMW\SPARQLStore;

use SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException;
use SMW\SPARQLStore\QueryEngine\FederateResultSet;
use SMW\SPARQLStore\QueryEngine\RawResultParser;
use SMWSparqlResultParser as SparqlResultParser;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * Specific modifications of the SPARQL database implementation for 4Store.
 *
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class FourstoreHttpDatabaseConnector extends GenericHttpDatabaseConnector {

	/**
	 * Execute a SPARQL query and return an FederateResultSet object
	 * that contains the results. Compared to GenericHttpDatabaseConnector::doQuery(),
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
	 * @return FederateResultSet
	 */
	public function doQuery( $sparql ) {

		if ( $this->m_queryEndpoint === '' ) {
			throw new BadHttpDatabaseResponseException( BadHttpDatabaseResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->m_queryEndpoint );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array('Accept: application/sparql-results+xml,application/xml;q=0.8' ));
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$parameterString = "query=" . urlencode( $sparql ) . "&restricted=1" .
			( ( $this->m_defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $this->m_defaultGraph ) : '' );

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );

		$xmlResult = $this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			$rawResultParser = new RawResultParser();
			$result = $rawResultParser->parse( $xmlResult );
		} else {
			$this->mapHttpRequestError( $this->m_queryEndpoint, $sparql );
			$result = new FederateResultSet( array(), array(), array(), FederateResultSet::ERROR_UNREACHABLE );
		}

		foreach ( $result->getComments() as $comment ) {
			if ( strpos( $comment, 'warning: hit complexity limit' ) === 0 ||
			     strpos( $comment, 'some results have been dropped' ) === 0 ) {
				$result->setErrorCode( FederateResultSet::ERROR_INCOMPLETE );
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
		$success = ( $affectedObjects->getErrorCode() == FederateResultSet::ERROR_NOERROR );

		foreach ( $affectedObjects as $expElements ) {
			if ( count( $expElements ) > 0 ) {
				$turtleName = TurtleSerializer::getTurtleNameForExpElement( reset( $expElements ) );
				$success = $this->delete( "$turtleName ?p ?o", "$turtleName ?p ?o", $extraNamespaces ) && $success;
			}
		}

		return $success;
	}

	/**
	 * Execute a HTTP-based SPARQL POST request according to
	 * http://www.w3.org/2009/sparql/docs/http-rdf-update/.
	 * The method throws exceptions based on
	 * GenericHttpDatabaseConnector::mapHttpRequestError(). If errors occur and this
	 * method does not throw anything, then an empty result with an error
	 * code is returned.
	 *
	 * This method is specific to 4Store since it uses POST parameters that
	 * are not given in the specification.
	 *
	 * @param $payload string Turtle serialization of data to send
	 *
	 * @return boolean
	 */
	public function doHttpPost( $payload ) {

		if ( $this->m_dataEndpoint === '' ) {
			throw new BadHttpDatabaseResponseException( BadHttpDatabaseResponseException::ERROR_NOSERVICE, "SPARQL POST with data: $payload", 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->m_dataEndpoint );
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$parameterString = "data=" . urlencode( $payload ) . '&graph=' .
			( ( $this->m_defaultGraph !== '' )? urlencode( $this->m_defaultGraph ) : 'default' ) .
			'&mime-type=application/x-turtle';

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );
		$this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			return true;
		}

		$this->mapHttpRequestError( $this->m_dataEndpoint, $payload );
		return false;
	}

	/**
	 * @see GenericHttpDatabaseConnector::doUpdate
	 *
	 * @note 4store 1.1.4 breaks on update if charset is set in the Content-Type header
	 *
	 * @since 2.0
	 */
	public function doUpdate( $sparql ) {

		if ( $this->m_updateEndpoint === '' ) {
			throw new BadHttpDatabaseResponseException( BadHttpDatabaseResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->m_updateEndpoint );
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$parameterString = "update=" . urlencode( $sparql );

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded' ) );

		$this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			return true;
		}

		$this->mapHttpRequestError( $this->m_updateEndpoint, $sparql );
		return false;
	}

}
