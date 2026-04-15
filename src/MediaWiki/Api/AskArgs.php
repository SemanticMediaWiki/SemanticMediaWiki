<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiFormatXml;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to query SMW by providing a query specified as
 * a list of conditions, printouts and parameters.
 *
 * @license GPL-2.0-or-later
 * @since 1.6.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AskArgs extends Query {

	/**
	 * @see ApiBase::execute
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();

		$parameterFormatter = new ApiRequestParameterFormatter( $this->extractRequestParams() );
		$outputFormat = 'json';

		$queryResult = $this->getQueryResult( $this->getQuery(
			$parameterFormatter->getAskArgsApiParameter( 'conditions' ),
			$parameterFormatter->getAskArgsApiParameter( 'printouts' ),
			$parameterFormatter->getAskArgsApiParameter( 'parameters' )
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
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams(): array {
		return [
			'conditions' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-askargs-param-conditions',
			],
			'printouts' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
				ParamValidator::PARAM_ISMULTI => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-askargs-param-printouts',
			],
			'parameters' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
				ParamValidator::PARAM_ISMULTI => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-askargs-param-parameters',
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
			'action=askargs&conditions=Modification%20date::%2B&printouts=Modification%20date&parameters=|sort%3DModification%20date|order%3Ddesc'
				=> 'apihelp-askargs-example-1',
		];
	}

}
