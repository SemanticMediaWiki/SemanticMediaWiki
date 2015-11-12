<?php

namespace SMW\MediaWiki\Api;

use ApiBase;

/**
 * API module to query SMW by providing a query specified as
 * a list of conditions, printouts and parameters.
 *
 * @license GNU GPL v2+
 * @since 1.6.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class AskArgs extends Query {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$parameterFormatter = new ApiRequestParameterFormatter( $this->extractRequestParams() );
		$outputFormat = 'json';

		$queryResult = $this->getQueryResult( $this->getQuery(
			$parameterFormatter->getAskArgsApiParameter( 'conditions' ),
			$parameterFormatter->getAskArgsApiParameter( 'printouts' ),
			$parameterFormatter->getAskArgsApiParameter( 'parameters' )
		) );

		if ( $this->getMain()->getPrinter() instanceof \ApiFormatXml ) {
			$outputFormat = 'xml';
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
		return array(
			'conditions' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => true,
			),
			'printouts' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
			'parameters' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'conditions' => 'The query conditions, i.e. the requirements for a subject to be included',
			'printouts'  => 'The query printouts, i.e. the properties to show per subject',
			'parameters' => 'The query parameters, i.e. all non-condition and non-printout arguments',
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return array(
			'API module to query SMW by providing a query specified as a list of conditions, printouts and parameters.'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=askargs&conditions=Modification%20date::%2B&printouts=Modification%20date&parameters=|sort%3DModification%20date|order%3Ddesc',
		);
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
