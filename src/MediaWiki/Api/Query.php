<?php

namespace SMW\MediaWiki\Api;

use ApiBase;
use SMW\Query\QueryResult;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMWQuery;
use SMWQueryProcessor;

/**
 * Base for API modules that query SMW
 *
 * @ingroup Api
 *
 * @license GPL-2.0-or-later
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
	protected function getQuery( $queryString, array $printouts, array $parameters = [] ) {
		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );

		$query = SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
			SMWQueryProcessor::SPECIAL_PAGE,
			'',
			$printouts
		);

		$query->setOption( SMWQuery::PROC_CONTEXT, 'API' );

		return $query;
	}

	/**
	 * Run the actual query and return the result.
	 *
	 * @since 1.6.2
	 *
	 * @param SMWQuery $query
	 *
	 * @return QueryResult
	 */
	protected function getQueryResult( SMWQuery $query ) {
		return ApplicationFactory::getInstance()->getQuerySourceFactory()->get( $query->getQuerySource() )->getQueryResult( $query );
	}

	/**
	 * Add the query result to the API output.
	 *
	 * @since 1.6.2
	 *
	 * @param QueryResult $queryResult
	 */
	protected function addQueryResult( QueryResult $queryResult, $outputFormat = 'json' ) {
		$result = $this->getResult();

		$resultFormatter = new ApiQueryResultFormatter( $queryResult );
		$resultFormatter->setIsRawMode( ( strpos( strtolower( $outputFormat ), 'xml' ) !== false ) );
		$resultFormatter->doFormat();

		if ( $resultFormatter->getContinueOffset() ) {
		// $result->disableSizeCheck();
			$result->addValue( null, 'query-continue-offset', $resultFormatter->getContinueOffset() );
		// $result->enableSizeCheck();
		}

		$result->addValue(
			null,
			$resultFormatter->getType(),
			$resultFormatter->getResult()
		);
	}

}
