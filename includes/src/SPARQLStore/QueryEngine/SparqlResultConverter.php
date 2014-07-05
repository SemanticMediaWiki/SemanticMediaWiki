<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\Store;

use SMWSparqlResultWrapper as SparqlResultWrapper;
use SMWExporter as Exporter;
use SMWQueryResult as QueryResult;
use SMWQuery as Query;

/**
 * Convert SPARQL SparqlResultWrapper object to an QueryResult object
 *
 * @ingroup Store
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 */
class SparqlResultConverter {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since  1.9.3
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * This function is used to generate instance query results, and the given
	 * result wrapper must have an according format (one result column that
	 * contains URIs of wiki pages).
	 *
	 * @param SparqlResultWrapper $sparqlResultWrapper
	 * @param Query $query QueryResults hold a reference to original query
	 *
	 * @return QueryResult
	 */
	public function convertToQueryResult( SparqlResultWrapper $sparqlResultWrapper, Query $query ) {

		if ( $query->querymode === Query::MODE_COUNT ) {
			return $this->makeQueryResultForCount( $sparqlResultWrapper, $query );
		}

		return $this->makeQueryResultForInstance( $sparqlResultWrapper,$query );
	}

	private function makeQueryResultForCount( SparqlResultWrapper $sparqlResultWrapper, Query $query ) {

		$queryResult = new QueryResult(
			$query->getDescription()->getPrintrequests(),
			$query,
			array(),
			$this->store,
			false
		);

		if ( $sparqlResultWrapper->getErrorCode() === SparqlResultWrapper::ERROR_NOERROR ) {
			$queryResult->setCountValue( $sparqlResultWrapper->getNumericValue() );
		} else {
			$queryResult->addErrors( array( wfMessage( 'smw_db_sparqlqueryproblem' )->inContentLanguage()->text() ) );
		}

		return $queryResult;
	}

	private function makeQueryResultForInstance( SparqlResultWrapper $sparqlResultWrapper, Query $query ) {

		$resultDataItems = array();

		foreach ( $sparqlResultWrapper as $resultRow ) {
			if ( count( $resultRow ) > 0 ) {
				$dataItem = Exporter::findDataItemForExpElement( $resultRow[0] );

				if ( !is_null( $dataItem ) ) {
					$resultDataItems[] = $dataItem;
				}
			}
		}

		if ( $sparqlResultWrapper->numRows() > $query->getLimit() ) {
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

		switch ( $sparqlResultWrapper->getErrorCode() ) {
			case SparqlResultWrapper::ERROR_NOERROR: break;
			case SparqlResultWrapper::ERROR_INCOMPLETE:
				$result->addErrors( array( wfMessage( 'smw_db_sparqlqueryincomplete' )->inContentLanguage()->text() ) );
			break;
			default:
				$result->addErrors( array( wfMessage( 'smw_db_sparqlqueryproblem' )->inContentLanguage()->text() ) );
			break;
		}

		return $result;
	}

}
