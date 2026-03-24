<?php

namespace SMW\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\QueryEngine\XmlResponseParser;
use SMW\Utils\Url;

/**
 * @see https://jena.apache.org/documentation/serving_data/index.html
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class FusekiRepositoryConnector extends GenericRepositoryConnector {

	/**
	 * @see GenericRepositoryConnector::doQuery
	 */
	public function doQuery( $sparql ): RepositoryResult {
		if ( $this->repositoryClient->getQueryEndpoint() === '' ) {
			throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_NOSERVICE, $sparql, 'not specified' );
		}

		$defaultGraph = $this->repositoryClient->getDefaultGraph();

		$parameterString = "query=" . urlencode( $sparql ) .
			( ( $defaultGraph !== '' ) ? '&default-graph-uri=' . urlencode( $defaultGraph ) : '' ) . '&output=xml';

		$request = $this->httpRequestFactory->create(
			$this->repositoryClient->getQueryEndpoint(),
			array_merge( $this->getBaseOptions(), [
				'method' => 'POST',
				'postData' => $parameterString,
			] ),
			__METHOD__
		);

		$request->setHeader( 'Accept', 'application/sparql-results+xml,application/xml;q=0.8' );
		$request->setHeader( 'Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8' );

		$status = $request->execute();
		$this->lastErrorCode = $request->getStatus();

		if ( $status->isOK() ) {
			$xmlResponseParser = new XmlResponseParser();
			return $xmlResponseParser->parse( $request->getContent() );
		}

		$this->mapHttpRequestError(
			$request->getStatus(),
			$this->repositoryClient->getQueryEndpoint(),
			$sparql
		);

		$repositoryResult = new RepositoryResult();
		$repositoryResult->setErrorCode( RepositoryResult::ERROR_UNREACHABLE );

		return $repositoryResult;
	}

	/**
	 * @since 3.2
	 *
	 * @see GenericRepositoryConnector::getVersion
	 */
	public function getVersion(): string {
		$url = new Url(
			$this->repositoryClient->getQueryEndpoint()
		);

		// https://jena.apache.org/documentation/fuseki2/fuseki-server-protocol.html
		$request = $this->httpRequestFactory->create(
			$url->path( '/$/server' ),
			$this->getBaseOptions(),
			__METHOD__
		);

		$status = $request->execute();

		if ( $status->isOK() ) {
			$httpResponse = $request->getContent();

			if ( is_string( $httpResponse ) ) {
				$httpResponse = json_decode( $httpResponse, true );

				return $httpResponse['version'] ?? '/na';
			}
		}

		return 'n/a';
	}

}
