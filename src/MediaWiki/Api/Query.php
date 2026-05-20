<?php

namespace SMW\MediaWiki\Api;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use SMW\Query\Query as SMWQuery;
use SMW\Query\QueryProcessor;
use SMW\Query\QueryResult;
use SMW\Query\QuerySourceFactory;

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
	 * @since 7.0.0
	 */
	public function __construct(
		ApiMain $main,
		string $action,
		private readonly QuerySourceFactory $querySourceFactory
	) {
		parent::__construct( $main, $action );
	}

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
		QueryProcessor::addThisPrintout( $printouts, $parameters );

		$query = QueryProcessor::createQuery(
			$queryString,
			QueryProcessor::getProcessedParams( $parameters, $printouts ),
			QueryProcessor::SPECIAL_PAGE,
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
	 * @return QueryResult
	 */
	protected function getQueryResult( SMWQuery $query ) {
		return $this->querySourceFactory->get( $query->getQuerySource() )->getQueryResult( $query );
	}

	/**
	 * Add the query result to the API output.
	 *
	 * @since 1.6.2
	 */
	protected function addQueryResult( QueryResult $queryResult, $outputFormat = 'json' ): void {
		$result = $this->getResult();

		$resultFormatter = new ApiQueryResultFormatter( $queryResult );
		$resultFormatter->setIsRawMode( ( strpos( strtolower( $outputFormat ), 'xml' ) !== false ) );
		$resultFormatter->doFormat();

		// Cursor mode is authoritative: when the query ran with a cursor
		// the response uses ONLY `query-continue-cursor` (or omits the
		// continuation field entirely on the final page). Legacy mode
		// emits `query-continue-offset` unchanged. Mixing the two would
		// let a cursor-aware client accidentally chain into a
		// `?offset=N&cursor=...` request, which has unspecified semantics.
		$cursorMode = $queryResult->getQuery()->getCursorAfter() !== null;
		$nextCursor = $queryResult->getNextCursor();

		if ( $cursorMode ) {
			if ( $nextCursor !== null ) {
				$result->addValue( null, 'query-continue-cursor', $nextCursor );
			}
		} elseif ( $resultFormatter->getContinueOffset() ) {
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
