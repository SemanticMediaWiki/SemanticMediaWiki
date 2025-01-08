<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use Html;
use RuntimeException;
use SMW\DIProperty;
use SMW\Localizer\Message;
use SMW\Query\QueryResult;
use SMW\Query\Result\FilterMap;
use SMW\Store;
use SMWQuery as Query;
use SMWQueryProcessor as QueryProcessor;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ResultFetcher {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var int
	 */
	private $totalCount = 0;

	/**
	 * @var int
	 */
	private $limit = 0;

	/**
	 * @var int
	 */
	private $offset = 0;

	/**
	 * @var bool
	 */
	private $hasFurtherResults = false;

	/**
	 * @var string
	 */
	private $queryString = '';

	/**
	 * @var QueryResult
	 */
	private $queryResult;

	/**
	 * @var
	 */
	private $params;

	/**
	 * @var string
	 */
	private $format = '';

	/**
	 * @var
	 */
	private $valueFilters = [];

	/**
	 * @var
	 */
	private $propertyFilters = [];

	/**
	 * @var
	 */
	private $categoryFilters = [];

	/**
	 * @var
	 */
	private $errors = [];

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @return int
	 */
	public function getTotalCount(): int {
		return $this->totalCount;
	}

	/**
	 * @since 3.2
	 *
	 * @return int
	 */
	public function getLimit(): int {
		return $this->limit;
	}

	/**
	 * @since 3.2
	 *
	 * @return int
	 */
	public function getOffset(): int {
		return $this->offset;
	}

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function hasFurtherResults(): bool {
		return $this->hasFurtherResults;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getQueryString(): string {
		return $this->queryString;
	}

	/**
	 * @since 3.2
	 *
	 * @return QueryResult|null
	 */
	public function getQueryResult(): ?QueryResult {
		return $this->queryResult;
	}

	/**
	 * @since 3.2
	 *
	 * @return
	 */
	public function getPropertyFilters(): array {
		return $this->propertyFilters;
	}

	/**
	 * @since 3.2
	 *
	 * @return
	 */
	public function getCategoryFilters(): array {
		return $this->categoryFilters;
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getValueFilters(): array {
		return $this->valueFilters;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getHtml(): string {
		if ( $this->errors !== [] ) {
			$msg = '';
			foreach ( $this->errors as $error ) {
				$msg .= Message::decode( $error );
			}

			return Html::errorBox( $msg );
		}

		if ( $this->queryResult === null ) {
			throw new RuntimeException( "Missing a `QueryResult` object, `ResultFetcher::fetchQueryResult` wasn't executed!" );
		}

		$printer = QueryProcessor::getResultPrinter(
			$this->format,
			QueryProcessor::SPECIAL_PAGE
		);

		$printer->setShowErrors( false );
		$html = $printer->getResult( $this->queryResult, $this->params, SMW_OUTPUT_HTML );

		if ( $html === '' ) {
			$html = Html::warningBox(
				Message::get( [ 'smw-facetedsearch-no-output', $this->format ] )
			);
		}

		return $html;
	}

	/**
	 * @since 3.2
	 *
	 * @param ParametersProcessor $parametersProcessor
	 */
	public function fetchQueryResult( ParametersProcessor $parametersProcessor ) {
		[ $queryString, $parameters, $printRequests ] = QueryProcessor::getComponentsFromFunctionParams(
			$parametersProcessor->getParameters(),
			false
		);

		$this->format = $parametersProcessor->getFormat();
		$this->queryString = $queryString;

		// Copy the printout to retain the original state while in case of no
		// specific subject (THIS) request extend the query with a
		// `PrintRequest::PRINT_THIS` column
		QueryProcessor::addThisPrintout(
			$printRequests,
			$parameters
		);

		$this->params = QueryProcessor::getProcessedParams(
			$parameters,
			$printRequests
		);

		$query = QueryProcessor::createQuery(
			$queryString,
			$this->params,
			QueryProcessor::SPECIAL_PAGE,
			$this->format,
			$printRequests
		);

		$this->errors = $query->getErrors();

		if ( $this->errors !== [] ) {
			return;
		}

		/**
		 * Running a total count for the query condition to indicate
		 * what can be exepcted outside of the limit/offset range
		 */
		$cquery = clone $query;
		$cquery->querymode = Query::MODE_COUNT;
		$cquery->setOffset( 0 );
		$totalCount = $this->store->getQueryResult( $cquery );

		$this->totalCount = $totalCount instanceof QueryResult ? $totalCount->getCountValue() : 0;

		if ( $this->totalCount < $query->getOffset() ) {
			$query->setOffset( 0 );
		}

		/**
		 * @var QueryResult
		 */
		$this->queryResult = $this->store->getQueryResult(
			$query
		);

		$filterMap = $this->queryResult->getFilterMap();

		$this->hasFurtherResults = $this->queryResult->hasFurtherResults();

		$this->propertyFilters = $filterMap->getCountListByType(
			FilterMap::PROPERTY_LIST
		);

		$this->categoryFilters = $filterMap->getCountListByType(
			FilterMap::CATEGORY_LIST
		);

		$this->limit = $query->getLimit();
		$this->offset = $query->getOffset();

		$results = $this->queryResult->getResults();
		$valueFilterResult = [];

		// To retain the list of all values available for a property (hereby make
		// it possible to combine (disjunction) them together run an additional
		// query where the specific value restriction is removed to create a value
		// filter list for all matchable subjects on a specific selected property
		if ( $parametersProcessor->getValueFilters() !== [] ) {

		// $_queryString = $queryString;

			foreach ( $parametersProcessor->getValueFilters() as $prop => $conds ) {

				$_queryString = str_replace( $conds, '', $queryString );

				$vquery = QueryProcessor::createQuery(
					$_queryString,
					$this->params,
					QueryProcessor::SPECIAL_PAGE,
					$this->format,
					$printRequests
				);

				$vquery->querymode = Query::MODE_COUNT;

				$qr = $this->store->getQueryResult(
					$vquery
				);

				// We cannot rely on the limit set by the original query since it may
				// have values outside the range that are hidden by the fact that we
				// have restricted the query by a particular value, now we want to know
				// every possible value match for a non value specific query hence
				// we first need to find out how many matches we can expect and extend
				// the limit accordingly to ensure we get the entire list of values
				// in order to correctly estimate the expected value counts.
				$count = $qr->getCountValue();

				$vquery->setLimit( $count );
				$vquery->querymode = Query::MODE_INSTANCES;

				$qr = $this->store->getQueryResult(
					$vquery
				);

				$subjects = [];

				foreach ( $qr->getResults() as $result ) {
					$subjects[] = $result->getSha1();
				}

				$valueFilterResult[$prop] = $subjects;
			}
		}

		$this->findValueFilters( $results, $valueFilterResult, $parametersProcessor->getPropertyFilters() );
	}

	private function findValueFilters( $results, $valueFilterResult, array $propertyFilters ) {
		if ( $propertyFilters === [] ) {
			return;
		}

		$subjects = [];

		foreach ( $results as $result ) {
			$subjects[] = $result->getSha1();
		}

		$byGroupPropertyValuesLookup = $this->store->service( 'ByGroupPropertyValuesLookup' );

		$valueFilters = [];
		$rawFilter = [];

		foreach ( $propertyFilters as $label ) {
			$list = $valueFilterResult[$label] ?? $subjects;

			$property = DIProperty::newFromUserLabel( $label );

			$valuesGroup = $byGroupPropertyValuesLookup->findValueGroups(
				$property,
				$list
			);

			$valueFilters[$label] = $valuesGroup['groups'];
			$rawFilter[$label] = $valuesGroup['raw'];
		}

		$this->valueFilters = [
			'filter' => $valueFilters,
			'raw' => $rawFilter
		];
	}

}
