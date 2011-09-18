<?php

/**
 * Base for API modules that query SMW.
 *
 * @since 1.6.2
 *
 * @file ApiSMWQuery.php
 * @ingroup SMW
 * @ingroup API
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ApiSMWQuery extends ApiBase {
	
	/**
	 * Query parameters.
	 * 
	 * @since 1.6.2
	 * @var array
	 */
	protected $parameters;
	
	/**
	 * Query printeouts.
	 * 
	 * @since 1.6.2
	 * @var array
	 */
	protected $printeouts;
	
	/**
	 * 
	 * @return SMWQuery
	 */
	protected function getQuery( $queryString, array $printeouts ) {
		return SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( $this->parameters ),
			SMWQueryProcessor::SPECIAL_PAGE,
			'',
			$printeouts
		);
	}
	
	/**
	 * 
	 * @param SMWQuery $query
	 * 
	 * @return SMWQueryResult
	 */
	protected function getQueryResult( SMWQuery $query ) {
		 return smwfGetStore()->getQueryResult( $query );
	}
	
	protected function addQueryResult( SMWQueryResult $queryResult ) {
		$serialized = $queryResult->serializeToArray();
		$result = $this->getResult();

		$result->setIndexedTagName( $serialized['results'], 'result' );
		$result->setIndexedTagName( $serialized['printrequests'], 'printrequest' );
		
		$result->addValue( 'query', null, $serialized );
		
		if ( $queryResult->hasFurtherResults() ) {
			// TODO: obtain continuation data from store
			$result->disableSizeCheck();
			$result->addValue( 'query-continue', null, 0 );
			$result->enableSizeCheck();
		}
	}
	
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}
	
	protected function requireParameters( array $params, array $required ) {
		foreach ( $required as $param ) {
			if ( !isset( $params[$param] ) ) {
				$this->dieUsageMsg( array( 'missingparam', $param ) );
			}
		}
	}
	
}
