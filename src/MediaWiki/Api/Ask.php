<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiFormatXml;
use SMW\Query\QueryProcessor;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to query SMW by providing a query in the ask language.
 *
 * @ingroup Api
 *
 * @license GPL-2.0-or-later
 * @since 1.6.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class Ask extends Query {

	/**
	 * @see ApiBase::execute
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();

		$parameterFormatter = new ApiRequestParameterFormatter( $this->extractRequestParams() );
		$outputFormat = 'json';

		[ $queryString, $parameters, $printouts ] = QueryProcessor::getComponentsFromFunctionParams( $parameterFormatter->getAskApiParameters(), false );

		$queryResult = $this->getQueryResult( $this->getQuery(
			$queryString,
			$printouts,
			$parameters
		) );

		if ( $this->getMain()->getPrinter() instanceof ApiFormatXml ) {
			$outputFormat = 'xml';
		}

		if ( isset( $params['api_version'] ) ) {
			$queryResult->setSerializerVersion( (int)$params['api_version'] );
		}

		$this->addQueryResult( $queryResult, $outputFormat );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams(): array {
		return [
			'query' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-ask-param-query',
			],
			'api_version' => [
				ParamValidator::PARAM_TYPE => [ '2', '3' ],
				ParamValidator::PARAM_DEFAULT => '2',
				ApiBase::PARAM_HELP_MSG => 'apihelp-ask-param-api-version',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		return [
			'action=ask&query=[[Modification%20date::%2B]]|%3FModification%20date|sort%3DModification%20date|order%3Ddesc'
				=> 'apihelp-ask-example-1',
			'action=ask&query=[[Modification%20date::%2B]]|limit%3D5|offset%3D1'
				=> 'apihelp-ask-example-2',
		];
	}

}
