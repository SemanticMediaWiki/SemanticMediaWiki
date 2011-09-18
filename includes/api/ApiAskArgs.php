<?php

/**
 * API module to query SMW by providing a query specified as
 * a list of conditions, printeouts and parameters. 
 *
 * @since 1.6.2
 *
 * @file ApiAskArgs.php
 * @ingroup SMW
 * @ingroup API
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiAskArgs extends ApiSMWQuery {
	
	public function execute() {
		$params = $this->extractRequestParams();
		$this->requireParameters( $params, array( 'conditions' ) );
		$this->parameters = $params['parameters'];
		
		$query = $this->getQuery( 
			implode( array_map( array( __CLASS__, 'wrapCondition' ), $params['conditions'] ) ),
			array_map( array( __CLASS__, 'printeoutFromString' ), $params['printeouts'] )
		);
		
		$this->addQueryResult( $this->getQueryResult( $query ) );
	}
	
	public static function wrapCondition( $c ) {
		return "[[$c]]"; 
	}
	
	public static function printeoutFromString( $printeout ) {
		return new SMWPrintRequest(
			SMWPrintRequest::PRINT_PROP,
			$printeout,
			SMWPropertyValue::makeUserProperty( $printeout )
		);
	}

	public function getAllowedParams() {
		return array(
			'conditions' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'printeouts' => array(
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
	
	public function getParamDescription() {
		return array(
			'conditions' => 'The query conditions, ie the requirements for a subject to be included',
			'printeouts' => 'The query printeouts, ie the properties to show per subject',
			'parameters' => 'The query parameters, ie all non-condition and non-printeout arguments',
		);
	}
	
	public function getDescription() {
		return array(
			'API module to query SMW by providing a query specified as a list of conditions, printeouts and parameters.
			This API module is in alpha stage, and likely to see changes in upcomming versions of SMW.'
		);
	}
	
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=askargs&conditions=Modification date::%2B&printeouts=Modification date&parameters=|sort%3DModification date|order%3Ddesc',
		);
	}	
	
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}		
	
}
