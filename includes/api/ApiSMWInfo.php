<?php

/**
 * API module to obtain info about the SMW install,
 * primerily targeted at usage by the SMW registry.
 *
 * @since 1.6
 *
 * @file ApiSMWInfo.php
 * @ingroup SMW
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiSMWInfo extends ApiBase {
	
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}
	
	public function execute() {
		$params = $this->extractRequestParams();
		$requestedInfo = $params['info'];
		$resultInfo = array();
		
		if ( in_array( 'proppagecount', $requestedInfo ) ) {
			$resultInfo['proppagecount'] = wfGetDB( DB_SLAVE )->estimateRowCount(
				'page',
				'*',
				array(
					'page_namespace' => SMW_NS_PROPERTY
				)
			);	
		}
		
		if ( in_array( 'propcount', $requestedInfo )
			|| in_array( 'usedpropcount', $requestedInfo ) 
			|| in_array( 'declaredpropcount', $requestedInfo ) ) {

			$semanticStats = smwfGetStore()->getStatistics();
			
			$map = array(
				'propcount' => 'PROPUSES',
				'usedpropcount' => 'USEDPROPS',
				'declaredpropcount' => 'DECLPROPS',
			);
			
			foreach ( $map as $apiName => $smwName ) {
				if ( in_array( $apiName, $requestedInfo ) ) {
					$resultInfo[$apiName] = $semanticStats[$smwName];
				}
			}
		}
		
		$this->getResult()->addValue(
			null,
			'info',
			$resultInfo
		);
	}

	public function getAllowedParams() {
		return array(
			'info' => array(
				ApiBase::PARAM_DFLT => 'propcount|usedpropcount|declaredpropcount',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'propcount',
					'usedpropcount',
					'declaredpropcount',
					'proppagecount',
				)
			),
		);
	}
	
	public function getParamDescription() {
		return array(
			'info' => 'The info to provide.'
		);
	}
	
	public function getDescription() {
		return array(
			'API module get info about this SMW install.'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=smwinfo&info=proppagecount|propcount',
		);
	}	
	
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}		
	
}
