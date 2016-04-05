<?php

namespace SMW\SPARQLStore\QueryEngine;

use RuntimeException;
use SMW\Exporter\Element;
use SMW\Query\DebugOutputFormatter;
use SMW\Query\Language\ThingDescription;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\RepositoryConnection;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;

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
	 * @var RepositoryConnection
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
	 * @since  2.0
	 *
	 * @param RepositoryConnection $connection
	 * @param CompoundConditionBuilder $compoundConditionBuilder
	 * @param QueryResultFactory $queryResultFactory
	 * @param EngineOptions|null $EngineOptions
	 */
	// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
	public function __construct( RepositoryConnection $connection, CompoundConditionBuilder $compoundConditionBuilder, QueryResultFactory $queryResultFactory, EngineOptions $engineOptions = null ) {
	// @codingStandardsIgnoreEnd
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

		if ( ( !$this->engineOptions->get( 'smwgIgnoreQueryErrors' ) || $query->getDescription() instanceof ThingDescription ) &&
		     $query->querymode != Query::MODE_DEBUG &&
		     count( $query->getErrors() ) > 0 ) {
			return $this->queryResultFactory->newEmptyQueryResult( $query, false );
		}

		// don't query, but return something to the printer
		if ( $query->querymode == Query::MODE_NONE || $query->getLimit() < 1 ) {
			return $this->queryResultFactory->newEmptyQueryResult( $query, true );
		}

		$this->compoundConditionBuilder->setSortKeys( $query->sortkeys );

		$compoundCondition = $this->compoundConditionBuilder->buildCondition(
			$query->getDescription()
		);

		$query->addErrors(
			$this->compoundConditionBuilder->getErrors()
		);

		if ( $query->querymode == Query::MODE_DEBUG ) {
			return $this->getDebugQueryResult( $query, $compoundCondition );
		} elseif ( $query->querymode == Query::MODE_COUNT ) {
			return $this->getCountQueryResult( $query, $compoundCondition );
		}

		return $this->getInstanceQueryResult( $query, $compoundCondition );
	}

	private function getCountQueryResult( Query $query, Condition $compoundCondition ) {

		if ( $this->isSingletonConditionWithElementMatch( $compoundCondition ) ) {
			if ( $compoundCondition->condition === '' ) { // all URIs exist, no querying
				return 1;
			} else {
				$condition = $this->compoundConditionBuilder->convertConditionToString( $compoundCondition );
				$namespaces = $compoundCondition->namespaces;
				$askQueryResult = $this->connection->ask( $condition, $namespaces );

				return $askQueryResult->isBooleanTrue() ? 1 : 0;
			}
		} elseif ( $compoundCondition instanceof FalseCondition ) {
			return 0;
		}

		$condition = $this->compoundConditionBuilder->convertConditionToString( $compoundCondition );
		$namespaces = $compoundCondition->namespaces;

		$options = $this->getOptions( $query, $compoundCondition );
		$options['DISTINCT'] = true;

		$repositoryResult = $this->connection->selectCount(
			'?' . self::RESULT_VARIABLE,
			$condition,
			$options,
			$namespaces
		);

		return $this->queryResultFactory->newQueryResult( $repositoryResult, $query );
	}

	private function getInstanceQueryResult( Query $query, Condition $compoundCondition ) {

		if ( $this->isSingletonConditionWithElementMatch( $compoundCondition ) ) {
			$matchElement = $compoundCondition->matchElement;

			if ( $compoundCondition->condition === '' ) { // all URIs exist, no querying
				$results = array( array ( $matchElement ) );
			} else {
				$condition = $this->compoundConditionBuilder->convertConditionToString( $compoundCondition );
				$namespaces = $compoundCondition->namespaces;
				$askQueryResult = $this->connection->ask( $condition, $namespaces );
				$results = $askQueryResult->isBooleanTrue() ? array( array ( $matchElement ) ) : array();
			}

			$repositoryResult = new RepositoryResult( array( self::RESULT_VARIABLE => 0 ), $results );

		} elseif ( $compoundCondition instanceof FalseCondition ) {
			$repositoryResult = new RepositoryResult( array( self::RESULT_VARIABLE => 0 ), array() );
		} else {
			$condition = $this->compoundConditionBuilder->convertConditionToString( $compoundCondition );
			$namespaces = $compoundCondition->namespaces;

			$options = $this->getOptions( $query, $compoundCondition );
			$options['DISTINCT'] = true;

			$repositoryResult = $this->connection->select(
				'?' . self::RESULT_VARIABLE,
				$condition,
				$options,
				$namespaces
			);
		}

		return $this->queryResultFactory->newQueryResult( $repositoryResult, $query );
	}

	private function getDebugQueryResult( Query $query, Condition $compoundCondition ) {

		$entries = array();

		if ( $this->isSingletonConditionWithElementMatch( $compoundCondition ) ) {
			if ( $compoundCondition->condition === '' ) { // all URIs exist, no querying
				$sparql = 'None (no conditions).';
			} else {
				$condition = $this->compoundConditionBuilder->convertConditionToString( $compoundCondition );
				$namespaces = $compoundCondition->namespaces;
				$sparql = $this->connection->getSparqlForAsk( $condition, $namespaces );
			}
		} elseif ( $compoundCondition instanceof FalseCondition ) {
			$sparql = 'None (conditions can not be satisfied by anything).';
		} else {
			$condition = $this->compoundConditionBuilder->convertConditionToString( $compoundCondition );
			$namespaces = $compoundCondition->namespaces;

			$options = $this->getOptions( $query, $compoundCondition );
			$options['DISTINCT'] = true;

			$sparql = $this->connection->getSparqlForSelect(
				'?' . self::RESULT_VARIABLE,
				$condition,
				$options,
				$namespaces
			);
		}

		$sparql = str_replace( array( '[',':',' ' ), array( '&#x005B;', '&#x003A;', '&#x0020;' ), $sparql );
		$entries['SPARQL Query'] = '<div class="smwpre">' . $sparql . '</div>';

		return DebugOutputFormatter::formatOutputFor( 'SPARQLStore', $entries, $query );
	}

	private function isSingletonConditionWithElementMatch( $condition ) {
		return $condition instanceof SingletonCondition && $condition->matchElement instanceof Element;
	}

	/**
	 * Get a SPARQL option array for the given query.
	 *
	 * @param Query $query
	 * @param Condition $compoundCondition (storing order by variable names)
	 *
	 * @return array
	 */
	protected function getOptions( Query $query, Condition $compoundCondition ) {

		$result = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
		if ( $this->engineOptions->get( 'smwgQSortingSupport' ) ) {

			$orderByString = '';

			foreach ( $query->sortkeys as $propkey => $order ) {

				if ( !is_string( $propkey ) ) {
					throw new RuntimeException( "Expected a string value as sortkey" );
				}

				if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $compoundCondition->orderVariables ) ) {
					$orderByString .= "$order(?" . $compoundCondition->orderVariables[$propkey] . ") ";
				} elseif ( ( $order == 'RANDOM' ) && $this->engineOptions->get( 'smwgQRandSortingSupport' ) ) {
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
