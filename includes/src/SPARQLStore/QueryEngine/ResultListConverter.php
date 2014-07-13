<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\Store;

use SMWExporter as Exporter;
use SMWQueryResult as QueryResult;
use SMWQuery as Query;

/**
 * Convert SPARQL FederateResultList object to an QueryResult object
 *
 * @ingroup Store
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ResultListConverter {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 2.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since  2.0
	 *
	 * @param Query $query QueryResults hold a reference to original query
	 * @param boolean $hasFurtherResults
	 *
	 * @return QueryResult
	 */
	public function newEmptyQueryResult( Query $query , $hasFurtherResults = false ) {
		return new QueryResult(
			$query->getDescription()->getPrintrequests(),
			$query,
			array(),
			$this->store,
			$hasFurtherResults
		);
	}

	/**
	 * This function is used to generate instance query results, and the given
	 * result wrapper must have an according format (one result column that
	 * contains URIs of wiki pages).
	 *
	 * @param FederateResultList $federateResultList
	 * @param Query $query QueryResults hold a reference to original query
	 *
	 * @return QueryResult
	 */
	public function convertToQueryResult( FederateResultList $federateResultList, Query $query ) {

		if ( $query->querymode === Query::MODE_COUNT ) {
			return $this->makeQueryResultForCount( $federateResultList, $query );
		}

		return $this->makeQueryResultForInstance( $federateResultList,$query );
	}

	private function makeQueryResultForCount( FederateResultList $federateResultList, Query $query ) {

		$queryResult = new QueryResult(
			$query->getDescription()->getPrintrequests(),
			$query,
			array(),
			$this->store,
			false
		);

		if ( $federateResultList->getErrorCode() === FederateResultList::ERROR_NOERROR ) {
			$queryResult->setCountValue( $federateResultList->getNumericValue() );
		} else {
			$queryResult->addErrors( array( wfMessage( 'smw_db_sparqlqueryproblem' )->inContentLanguage()->text() ) );
		}

		return $queryResult;
	}

	private function makeQueryResultForInstance( FederateResultList $federateResultList, Query $query ) {

		$resultDataItems = array();

		foreach ( $federateResultList as $resultRow ) {
			if ( count( $resultRow ) > 0 ) {
				$dataItem = Exporter::findDataItemForExpElement( $resultRow[0] );

				if ( !is_null( $dataItem ) ) {
					$resultDataItems[] = $dataItem;
				}
			}
		}

		if ( $federateResultList->numRows() > $query->getLimit() ) {
			array_pop( $resultDataItems );
			$hasFurtherResults = true;
		} else {
			$hasFurtherResults = false;
		}

		$result = new QueryResult(
			$query->getDescription()->getPrintrequests(),
			$query,
			$resultDataItems,
			$this->store,
			$hasFurtherResults
		);

		switch ( $federateResultList->getErrorCode() ) {
			case FederateResultList::ERROR_NOERROR: break;
			case FederateResultList::ERROR_INCOMPLETE:
				$result->addErrors( array( wfMessage( 'smw_db_sparqlqueryincomplete' )->inContentLanguage()->text() ) );
			break;
			default:
				$result->addErrors( array( wfMessage( 'smw_db_sparqlqueryproblem' )->inContentLanguage()->text() ) );
			break;
		}

		return $result;
	}

}
