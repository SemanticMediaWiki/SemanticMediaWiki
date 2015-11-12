<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\ApplicationFactory;
use SMWQuery;
use SMWQueryProcessor;
use SMWQueryResult;

/**
 * Base for API modules that query SMW
 *
 * @ingroup Api
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
abstract class Query extends ApiBase {

	/**
	 * Returns a query object for the provided query string and list of printouts.
	 *
	 * @since 1.6.2
	 *
	 * @param string $queryString
	 * @param array $printouts
	 * @param array $parameters
	 *
	 * @return SMWQuery
	 */
	protected function getQuery( $queryString, array $printouts, array $parameters = array() ) {

		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );

		return SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
			SMWQueryProcessor::SPECIAL_PAGE,
			'',
			$printouts
		);
	}

	/**
	 * Run the actual query and return the result.
	 *
	 * @since 1.6.2
	 *
	 * @param SMWQuery $query
	 *
	 * @return SMWQueryResult
	 */
	protected function getQueryResult( SMWQuery $query ) {
		 return ApplicationFactory::getInstance()->getStore()->getQueryResult( $query );
	}

	/**
	 * Add the query result to the API output.
	 *
	 * @since 1.6.2
	 *
	 * @param SMWQueryResult $queryResult
	 */
	protected function addQueryResult( SMWQueryResult $queryResult, $outputFormat = 'json' ) {

		$result = $this->getResult();

		$resultFormatter = new ApiQueryResultFormatter( $queryResult );
		$resultFormatter->setIsRawMode( ( strpos( strtolower( $outputFormat ), 'xml' ) !== false ) );
		$resultFormatter->doFormat();

		if ( $resultFormatter->getContinueOffset() ) {
		//	$result->disableSizeCheck();
			$result->addValue( null, 'query-continue-offset', $resultFormatter->getContinueOffset() );
		//	$result->enableSizeCheck();
		}

		$result->addValue(
			null,
			$resultFormatter->getType(),
			$resultFormatter->getResult()
		);
	}

}
