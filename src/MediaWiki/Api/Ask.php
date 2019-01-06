<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMWQueryProcessor;

/**
 * API module to query SMW by providing a query in the ask language.
 *
 * @ingroup Api
 *
 * @license GNU GPL v2+
 * @since 1.6.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class Ask extends Query {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();

		$parameterFormatter = new ApiRequestParameterFormatter( $this->extractRequestParams() );
		$outputFormat = 'json';

		list( $queryString, $parameters, $printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $parameterFormatter->getAskApiParameters(), false );

		$queryResult = $this->getQueryResult( $this->getQuery(
			$queryString,
			$printouts,
			$parameters
		) );

		if ( $this->getMain()->getPrinter() instanceof \ApiFormatXml ) {
			$outputFormat = 'xml';
		}

		if ( isset( $params['api_version'] ) ) {
			$queryResult->setSerializerVersion( $params['api_version'] );
		}

		$this->addQueryResult( $queryResult, $outputFormat );
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'query' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			],
			'api_version' => [
				ApiBase::PARAM_TYPE => [ 2, 3 ],
				ApiBase::PARAM_DFLT => 2,
				ApiBase::PARAM_HELP_MSG => 'apihelp-ask-parameter-api-version',
			],
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return [
			'query' => 'The query string in ask-language'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return [
			'API module to query SMW by providing a query in the ask language.'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	protected function getExamples() {
		return [
			'api.php?action=ask&query=[[Modification%20date::%2B]]|%3FModification%20date|sort%3DModification%20date|order%3Ddesc',
			'api.php?action=ask&query=[[Modification%20date::%2B]]|limit%3D5|offset%3D1'
		];
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . SMW_VERSION;
	}

}
