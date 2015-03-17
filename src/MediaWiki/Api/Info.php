<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\ApplicationFactory;

/**
 * API module to obtain info about the SMW install, primarily targeted at
 * usage by the SMW registry.
 *
 * @ingroup Api
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Info extends ApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();
		$requestedInfo = $params['info'];
		$resultInfo = array();

		if ( in_array( 'propcount', $requestedInfo )
			|| in_array( 'errorcount', $requestedInfo )
			|| in_array( 'usedpropcount', $requestedInfo )
			|| in_array( 'proppagecount', $requestedInfo )
			|| in_array( 'querycount', $requestedInfo )
			|| in_array( 'querysize', $requestedInfo )
			|| in_array( 'formatcount', $requestedInfo )
			|| in_array( 'conceptcount', $requestedInfo )
			|| in_array( 'subobjectcount', $requestedInfo )
			|| in_array( 'declaredpropcount', $requestedInfo ) ) {

			$semanticStats = ApplicationFactory::getInstance()->getStore()->getStatistics();

			$map = array(
				'propcount' => 'PROPUSES',
				'errorcount' => 'ERRORUSES',
				'usedpropcount' => 'USEDPROPS',
				'declaredpropcount' => 'DECLPROPS',
				'proppagecount' => 'OWNPAGE',
				'querycount' => 'QUERY',
				'querysize' => 'QUERYSIZE',
				'conceptcount' => 'CONCEPTS',
				'subobjectcount' => 'SUBOBJECTS',
			);

			foreach ( $map as $apiName => $smwName ) {
				if ( in_array( $apiName, $requestedInfo ) ) {
					$resultInfo[$apiName] = $semanticStats[$smwName];
				}
			}

			if ( in_array( 'formatcount', $requestedInfo ) ) {
				$resultInfo['formatcount'] = array();
				foreach ( $semanticStats['QUERYFORMATS'] as $name => $count ) {
					$resultInfo['formatcount'][$name] = $count;
				}
			}
		}

		$this->getResult()->addValue( null, 'info', $resultInfo );
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'info' => array(
				ApiBase::PARAM_DFLT => 'propcount|usedpropcount|declaredpropcount',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'propcount',
					'errorcount',
					'usedpropcount',
					'declaredpropcount',
					'proppagecount',
					'querycount',
					'querysize',
					'formatcount',
					'conceptcount',
					'subobjectcount'
				)
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
			'info' => 'The info to provide.'
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
			'API module get info about this SMW install.'
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
			'api.php?action=smwinfo&info=proppagecount|propcount',
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
