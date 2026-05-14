<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use SMW\Query\QueryProcessor;
use SMW\Query\QueryResult;
use SMW\Services\ServicesFactory as ApplicationFactory;

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
	 * @return \SMW\Query\Query
	 */
	protected function getQuery( $queryString, array $printouts, array $parameters = [] ) {
		QueryProcessor::addThisPrintout( $printouts, $parameters );

		$query = QueryProcessor::createQuery(
			$queryString,
			QueryProcessor::getProcessedParams( $parameters, $printouts ),
			QueryProcessor::SPECIAL_PAGE,
			'',
			$printouts
		);

		$query->setOption( \SMW\Query\Query::PROC_CONTEXT, 'API' );

		return $query;
	}

	/**
	 * Run the actual query and return the result.
	 *
	 * @since 1.6.2
	 *
	 * @param \SMW\Query\Query $query
	 *
	 * @return QueryResult
	 */
	protected function getQueryResult( \SMW\Query\Query $query ) {
		return ApplicationFactory::getInstance()->getQuerySourceFactory()->get( $query->getQuerySource() )->getQueryResult( $query );
	}

	/**
	 * Add the query result to the API output.
	 *
	 * @since 1.6.2
	 *
	 * @param QueryResult $queryResult
	 */
	protected function addQueryResult( QueryResult $queryResult, $outputFormat = 'json' ): void {
		$result = $this->getResult();

		$resultFormatter = new ApiQueryResultFormatter( $queryResult );
		$resultFormatter->setIsRawMode( ( strpos( strtolower( $outputFormat ), 'xml' ) !== false ) );
		$resultFormatter->doFormat();

		if ( $resultFormatter->getContinueOffset() ) {
		// $result->disableSizeCheck();
			$result->addValue( null, 'query-continue-offset', $resultFormatter->getContinueOffset() );
		// $result->enableSizeCheck();
		}

		// Byte-additive: only emitted when the query ran in keyset cursor
		// mode and there are further results. Legacy clients that follow
		// `query-continue-offset` see exactly the pre-cursor response
		// shape. Mirrors the contract established by the Browse API
		// keyset PRs (#6804 / #6806 / #6808).
		$nextCursor = $queryResult->getNextCursor();
		if ( $nextCursor !== null ) {
			$result->addValue( null, 'query-continue-cursor', $nextCursor );
		}

		$result->addValue(
			null,
			$resultFormatter->getType(),
			$resultFormatter->getResult()
		);
	}

}
