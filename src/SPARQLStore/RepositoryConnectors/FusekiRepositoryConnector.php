<?php

namespace SMW\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\QueryEngine\XmlResponseParser;

/**
 * @see https://jena.apache.org/documentation/serving_data/index.html
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FusekiRepositoryConnector extends GenericRepositoryConnector {

	/**
	 * @see GenericRepositoryConnector::doQuery
	 */
	public function doQuery( $sparql ) {

		if ( $this->repositoryClient->getQueryEndpoint() === '' ) {
			throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$this->httpRequest->setOption( CURLOPT_URL, $this->repositoryClient->getQueryEndpoint() );

		$this->httpRequest->setOption( CURLOPT_HTTPHEADER, [
			'Accept: application/sparql-results+xml,application/xml;q=0.8',
			'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
		] );

		$this->httpRequest->setOption( CURLOPT_POST, true );

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$parameterString = "query=" . urlencode( $sparql ) .
			( ( $defaultGraph !== '' )? '&default-graph-uri=' . urlencode( $defaultGraph ) : '' ) . '&output=xml';

		$this->httpRequest->setOption( CURLOPT_POSTFIELDS, $parameterString );

		$httpResponse = $this->httpRequest->execute();

		if ( $this->httpRequest->getLastErrorCode() == 0 ) {
			$xmlResponseParser = new XmlResponseParser();
			return $xmlResponseParser->parse( $httpResponse );
		}

		$this->mapHttpRequestError( $this->repositoryClient->getQueryEndpoint(), $sparql );

		$repositoryResult = new RepositoryResult();
		$repositoryResult->setErrorCode( RepositoryResult::ERROR_UNREACHABLE );

		return $repositoryResult;
	}

}
