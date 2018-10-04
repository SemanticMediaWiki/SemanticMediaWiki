<?php

namespace SMW\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\QueryEngine\XmlResponseParser;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * Specific modifications of the SPARQL database implementation for 4Store.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class FourstoreRepositoryConnector extends GenericRepositoryConnector {

	/**
	 * Execute a SPARQL query and return an RepositoryResult object
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
	 * @return RepositoryResult
	 */
	public function doQuery( $sparql ) {

		if ( $this->repositoryClient->getQueryEndpoint() === '' ) {
			throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getQueryEndpoint() );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, ['Accept: application/sparql-results+xml,application/xml;q=0.8' ]);
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$parameterString = "query=" . urlencode( $sparql ) . "&restricted=1" .
			( ( $defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $defaultGraph ) : '' );

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );

		$httpResponse = $this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			$xmlResponseParser = new XmlResponseParser();
			$result = $xmlResponseParser->parse( $httpResponse );
		} else {
			$this->mapHttpRequestError( $this->repositoryClient->getQueryEndpoint(), $sparql );
			$result = new RepositoryResult();
			$result->setErrorCode( RepositoryResult::ERROR_UNREACHABLE );
		}

		foreach ( $result->getComments() as $comment ) {
			if ( strpos( $comment, 'warning: hit complexity limit' ) === 0 ||
			     strpos( $comment, 'some results have been dropped' ) === 0 ) {
				$result->setErrorCode( RepositoryResult::ERROR_INCOMPLETE );
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
	public function deleteContentByValue( $propertyName, $objectName, $extraNamespaces = [] ) {
		$affectedObjects = $this->select( '*', "?s $propertyName $objectName", [], $extraNamespaces );
		$success = ( $affectedObjects->getErrorCode() == RepositoryResult::ERROR_NOERROR );

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

		if ( $this->repositoryClient->getDataEndpoint() === '' ) {
			throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_NOSERVICE, "SPARQL POST with data: $payload", 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getDataEndpoint() );
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$parameterString = "data=" . urlencode( $payload ) . '&graph=' .
			( ( $defaultGraph !== '' )? urlencode( $defaultGraph ) : 'default' ) .
			'&mime-type=application/x-turtle';

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );
		$this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			return true;
		}

		$this->mapHttpRequestError( $this->repositoryClient->getDataEndpoint(), $payload );
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

		if ( $this->repositoryClient->getUpdateEndpoint() === '' ) {
			throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getUpdateEndpoint() );
		$this->httpRequest->setOption( CURLOPT_POST, true );

		$parameterString = "update=" . urlencode( $sparql );

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );
		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded' ] );

		$this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			return true;
		}

		$this->mapHttpRequestError( $this->repositoryClient->getUpdateEndpoint(), $sparql );
		return false;
	}

}
