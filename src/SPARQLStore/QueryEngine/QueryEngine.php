<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\FederateResultSet;
use SMW\Query\Language\ThingDescription;

use SMW\QueryOutputFormatter;

use SMWSparqlDatabase as SparqlDatabase;
use SMWQueryResult as QueryResult;
use SMWQuery as Query;

use RuntimeException;

/**
 * Class mapping SMWQuery objects to SPARQL, and for controlling the execution
 * of these queries to obtain suitable QueryResult objects.
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class QueryEngine {

	/// The name of the SPARQL variable that represents the query result.
	const RESULT_VARIABLE = 'result';

	/**
	 * @var SparqlDatabase
	 */
	private $connection;

	/**
	 * @var CompoundConditionBuilder
	 */
	private $compoundConditionBuilder;

	/**
	 * @var QueryResultFactory
	 */
	private $queryResultFactory;

	/**
	 * @var EngineOptions
	 */
	private $engineOptions;

	/**
	 * Copy of the SMWQuery sortkeys array to be used while building the
	 * SPARQL query conditions.
	 * @var array
	 */
	private $sortkeys;

	/**
	 * @since  2.0
	 *
	 * @param SparqlDatabase $connection
	 * @param CompoundConditionBuilder $compoundConditionBuilder
	 * @param QueryResultFactory $queryResultFactory
	 * @param EngineOptions|null $engineOptions
	 */
	public function __construct( SparqlDatabase $connection, CompoundConditionBuilder $compoundConditionBuilder, QueryResultFactory $queryResultFactory, EngineOptions $engineOptions = null ) {
		$this->connection = $connection;
		$this->compoundConditionBuilder = $compoundConditionBuilder;
		$this->queryResultFactory = $queryResultFactory;
		$this->engineOptions = $engineOptions;

		if ( $this->engineOptions === null ) {
			$this->engineOptions = new EngineOptions();
		}

		$this->compoundConditionBuilder->setResultVariable( self::RESULT_VARIABLE );
	}

	/**
	 * @since  2.0
	 * @param  Query $query
	 *
	 * @return QueryResult|string
	 */
	public function getQueryResult( Query $query ) {

		if ( ( !$this->engineOptions->ignoreQueryErrors || $query->getDescription() instanceof ThingDescription ) &&
		     $query->querymode != Query::MODE_DEBUG &&
		     count( $query->getErrors() ) > 0 ) {
			return $this->queryResultFactory->newEmptyQueryResult( $query, false );
		}

		// don't query, but return something to the printer
		if ( $query->querymode == Query::MODE_NONE ) {
			return $this->queryResultFactory->newEmptyQueryResult( $query, true );
		}

		if ( $query->querymode == Query::MODE_DEBUG ) {
			return $this->getDebugQueryResult( $query );
		} elseif ( $query->querymode == Query::MODE_COUNT ) {
			return $this->getCountQueryResult( $query );
		}

		return $this->getInstanceQueryResult( $query );
	}

	/**
	 * Get the output number for a query in counting mode.
	 *
	 * @note ignore sorting, just count
	 *
	 * @param Query $query
	 *
	 * @return integer
	 */
	public function getCountQueryResult( Query $query ) {

		// $countResultLookup = new CountResultLookup( $this->connection, $this->compoundConditionBuilder );
		// $countResultLookup->getQueryResult( $query );

		$this->sortkeys = array();

		$sparqlCondition = $this->compoundConditionBuilder
			->setSortKeys( $this->sortkeys )
			->buildCondition( $query->getDescription() );

		if ( $sparqlCondition instanceof SingletonCondition ) {
			if ( $sparqlCondition->condition === '' ) { // all URIs exist, no querying
				return 1;
			} else {
				$condition = $this->compoundConditionBuilder->convertConditionToString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$askQueryResult = $this->connection->ask( $condition, $namespaces );

				return $askQueryResult->isBooleanTrue() ? 1 : 0;
			}
		} elseif ( $sparqlCondition instanceof FalseCondition ) {
			return 0;
		}

		$condition = $this->compoundConditionBuilder->convertConditionToString( $sparqlCondition );
		$namespaces = $sparqlCondition->namespaces;

		$options = $this->getOptions( $query, $sparqlCondition );
		$options['DISTINCT'] = true;

		$federateResultSet = $this->connection->selectCount(
			'?' . self::RESULT_VARIABLE,
			$condition,
			$options,
			$namespaces
		);

		return $this->queryResultFactory->newQueryResult( $federateResultSet, $query );
	}

	/**
	 * Get the results for a query in instance retrieval mode.
	 *
	 * @param Query $query
	 *
	 * @return QueryResult
	 */
	public function getInstanceQueryResult( Query $query ) {

		$this->sortkeys = $query->sortkeys;

		$sparqlCondition = $this->compoundConditionBuilder
			->setSortKeys( $this->sortkeys )
			->buildCondition( $query->getDescription() );

		if ( $sparqlCondition instanceof SingletonCondition ) {
			$matchElement = $sparqlCondition->matchElement;

			if ( $sparqlCondition->condition === '' ) { // all URIs exist, no querying
				$results = array( array ( $matchElement ) );
			} else {
				$condition = $this->compoundConditionBuilder->convertConditionToString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$askQueryResult = $this->connection->ask( $condition, $namespaces );
				$results = $askQueryResult->isBooleanTrue() ? array( array ( $matchElement ) ) : array();
			}

			$federateResultSet = new FederateResultSet( array( self::RESULT_VARIABLE => 0 ), $results );

		} elseif ( $sparqlCondition instanceof FalseCondition ) {
			$federateResultSet = new FederateResultSet( array( self::RESULT_VARIABLE => 0 ), array() );
		} else {
			$condition = $this->compoundConditionBuilder->convertConditionToString( $sparqlCondition );
			$namespaces = $sparqlCondition->namespaces;

			$options = $this->getOptions( $query, $sparqlCondition );
			$options['DISTINCT'] = true;

			$federateResultSet = $this->connection->select(
				'?' . self::RESULT_VARIABLE,
				$condition,
				$options,
				$namespaces
			);
		}

		return $this->queryResultFactory->newQueryResult( $federateResultSet, $query );
	}

	/**
	 * Get the output string for a query in debugging mode.
	 *
	 * @param Query $query
	 *
	 * @return string
	 */
	public function getDebugQueryResult( Query $query ) {

		$this->sortkeys = $query->sortkeys;

		$sparqlCondition = $this->compoundConditionBuilder
			->setSortKeys( $this->sortkeys )
			->buildCondition( $query->getDescription() );

		$entries = array();

		if ( $sparqlCondition instanceof SingletonCondition ) {
			if ( $sparqlCondition->condition === '' ) { // all URIs exist, no querying
				$sparql = 'None (no conditions).';
			} else {
				$condition = $this->compoundConditionBuilder->convertConditionToString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$sparql = $this->connection->getSparqlForAsk( $condition, $namespaces );
			}
		} elseif ( $sparqlCondition instanceof FalseCondition ) {
			$sparql = 'None (conditions can not be satisfied by anything).';
		} else {
			$condition = $this->compoundConditionBuilder->convertConditionToString( $sparqlCondition );
			$namespaces = $sparqlCondition->namespaces;

			$options = $this->getOptions( $query, $sparqlCondition );
			$options['DISTINCT'] = true;

			$sparql = $this->connection->getSparqlForSelect(
				'?' . self::RESULT_VARIABLE,
				$condition,
				$options,
				$namespaces
			);
		}

		$sparql = str_replace( array( '[',':',' ' ), array( '&#x005B;', '&#x003A;', '&#x0020;' ), $sparql );
		$entries['SPARQL Query'] = "<pre>$sparql</pre>";

		return QueryOutputFormatter::formatDebugOutput( 'SPARQLStore', $entries, $query );
	}

	/**
	 * Get a SPARQL option array for the given query.
	 *
	 * @param Query $query
	 * @param Condition $sparqlCondition (storing order by variable names)
	 *
	 * @return array
	 */
	protected function getOptions( Query $query, Condition $sparqlCondition ) {

		$result = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
		if ( $this->engineOptions->sortingSupport ) {

			$orderByString = '';

			foreach ( $this->sortkeys as $propkey => $order ) {

				if ( !is_string( $propkey ) ) {
					throw new RuntimeException( "Expected a string value as sortkey" );
				}

				if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $sparqlCondition->orderVariables ) ) {
					$orderByString .= "$order(?" . $sparqlCondition->orderVariables[$propkey] . ") ";
				} elseif ( ( $order == 'RANDOM' ) && $this->engineOptions->randomSortingSupport ) {
					// not supported in SPARQL; might be possible via function calls in some stores
				}
			}

			if ( $orderByString !== '' ) {
				$result['ORDER BY'] = $orderByString;
			}
		}

		return $result;
	}

}
