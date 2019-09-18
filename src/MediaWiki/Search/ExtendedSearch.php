<?php

namespace SMW\MediaWiki\Search;

use SMW\Store;
use RuntimeException;
use SearchEngine;
use SMWQuery;
use SMWQueryResult as QueryResult;
use Title;

/**
 * Search engine that will try to find wiki pages by interpreting the search
 * term as an SMW query.
 *
 * If successful, the pages according to the query will be returned.
 * If not it falls back to the default search engine.
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author  Stephan Gambke
 */
class ExtendedSearch {

	/**
	 * To provide a wider search radius for the completion search
	 */
	const COMPLETION_SEARCH_EXTRA_SEARCH_SIZE = 10;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var SearchEngine
	 */
	private $fallbackSearchEngine;

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var string
	 */
	private $queryString = '';

	/**
	 * @var InfoLink
	 */
	private $queryLink;

	/**
	 * @var
	 */
	private $prefix;

	/**
	 * @var []
	 */
	private $extraPrefixMap = [];

	/**
	 * @var []
	 */
	private $namespaces = [];

	/**
	 * @var []
	 */
	private $searchableNamespaces = [];

	/**
	 * @var integer
	 */
	private $limit = 10;

	/**
	 * @var integer
	 */
	private $offset = 0;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param SearchEngine $fallbackSearchEngine
	 */
	public function __construct( Store $store, SearchEngine $fallbackSearchEngine ) {
		$this->store = $store;
		$this->fallbackSearchEngine = $fallbackSearchEngine;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $extraPrefixMap
	 */
	public function setExtraPrefixMap( array $extraPrefixMap ) {
		foreach ( $extraPrefixMap as $key => $value ) {
			if ( is_string( $key ) ) {
				$this->extraPrefixMap[] = $key;
			} else {
				$this->extraPrefixMap[] = $value;
			}
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function setQueryBuilder( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $prefix
	 */
	public function setPrefix( $prefix ) {
		$this->prefix = $prefix;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $namespaces
	 */
	public function setNamespaces( array $namespaces ) {
		$this->namespaces = $namespaces;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $searchableNamespaces
	 */
	public function setSearchableNamespaces( array $searchableNamespaces ) {
		$this->searchableNamespaces = $searchableNamespaces;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $limit
	 * @param integer $offset
	 */
	public function setLimitOffset( $limit, $offset = 0 ) {
		$this->limit = intval( $limit );
		$this->offset = intval( $offset );
	}

	/**
	 * @since 3.1
	 *
	 * @return integer
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @since 3.1
	 *
	 * @return integer
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getQueryString() {
		return $this->queryString;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getQueryLink() {
		return $this->queryLink;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getValidSorts() {
		return [

			// SemanticMediaWiki supported
			'title', 'recent', 'best',

			// MediaWiki default
			'relevance'
		];
	}

	/**
	 * Perform a title-only search query and return a result set.
	 *
	 * This method will try to find wiki pages by interpreting the search term as an SMW query.
	 *
	 * If successful, the pages according to the query will be returned.
	 * If not, it falls back to the default search engine.
	 *
	 * @param string $term Raw search term
	 *
	 * @return SearchResultSet|null
	 */
	public function searchTitle( $term ) {

		if ( $this->getSearchQuery( $term ) !== null ) {
			return null;
		}

		return $this->searchFallbackSearchEngine( $term, false );
	}

	/**
	 * Perform a full text search query and return a result set.
	 * If title searches are not supported or disabled, return null.
	 *
	 * @param string $term Raw search term
	 *
	 * @return SearchResultSet|\Status|null
	 */
	public function searchText( $term ) {

		if ( $this->getSearchQuery( $term ) !== null ) {
			return $this->newSearchResultSet( $term );
		}

		return $this->searchFallbackSearchEngine( $term, true );
	}

	/**
	 * Perform a completion search.
	 *
	 * @param string $search
	 *
	 * @return SearchSuggestionSet
	 */
	public function completionSearch( $search ) {

		$searchResultSet = null;
		$minLen = 3;

		// Avoid MW's auto formatting of title entities
		if ( $search !== '' ) {
			$search[0] = strtolower( $search[0] );
		}

		if ( $this->hasPrefixAndMinLenForCompletionSearch( $search, $minLen ) ) {
			if ( $this->getSearchQuery( $search ) !== null ) {
				// Lets widen the search in case we fetch subobjects
				$this->limit = $this->limit + self::COMPLETION_SEARCH_EXTRA_SEARCH_SIZE;
				$searchResultSet = $this->newSearchResultSet( $search, false, false );
			}

			if ( $searchResultSet instanceof SearchResultSet ) {
				return $searchResultSet->newSearchSuggestionSet();
			}
		}

		return $this->fallbackSearchEngine->completionSearch( $search );
	}

	private function hasPrefixAndMinLenForCompletionSearch( $search, $minLen ) {

		// Only act on when `in:foo`, `has:SomeProperty`, or `phrase:some text`
		// is actively used as prefix
		$defaultPrefixMap = [ 'in', 'has', 'phrase', 'not' ];

		foreach ( $defaultPrefixMap as $key ) {
			$prefix = "$key:";

			if ( ( $pos = stripos( $search, $prefix ) ) !== false && $pos == 0 ) {
				return true;
			}
		}

		foreach ( $this->extraPrefixMap as $key ) {
			$prefix = "$key:";

			if ( ( $pos = stripos( $search, $prefix ) ) !== false && $pos == 0 ) {
				return true;
			}
		}

		return false;
	}

	private function newSearchResultSet( $term, $count = true, $highlight = true ) {

		$query = $this->getSearchQuery( $term );

		if ( $query === null ) {
			return null;
		}

		$query->setOffset( $this->offset );
		$query->setLimit( $this->limit, false );
		$this->queryString = $query->getQueryString();

		$query->clearErrors();
		$query->setOption( 'highlight.fragment', $highlight );
		$query->setOption( SMWQuery::PROC_CONTEXT, 'SpecialSearch' );

		$result = $this->store->getQueryResult( $query );
		$this->errors = $query->getErrors();

		$this->queryLink = $result->getQueryLink();
		$this->queryLink->setParameter( $this->offset, 'offset' );
		$this->queryLink->setParameter( $this->limit, 'limit' );

		if ( $count ) {
			$query->querymode = SMWQuery::MODE_COUNT;
			$query->setOffset( 0 );

			$queryResult = $this->store->getQueryResult( $query );
			$count = $queryResult instanceof QueryResult ? $queryResult->getCountValue() : $queryResult;
		} else {
			$count = 0;
		}

		return new SearchResultSet( $result, $count );
	}

	/**
	 * @param String $term
	 *
	 * @return SMWQuery | null
	 */
	private function getSearchQuery( $term ) {

		if ( $this->queryBuilder === null ) {
			$this->queryBuilder = new QueryBuilder();
		}

		$this->queryString = $this->queryBuilder->getQueryString(
			$this->store,
			$term
		);

		$query = $this->queryBuilder->getQuery(
			$this->queryString
		);

		$this->queryBuilder->addSort( $query );

		$this->queryBuilder->addNamespaceCondition(
			$query,
			$this->searchableNamespaces
		);

		return $query;
	}

	private function searchFallbackSearchEngine( $term, $fulltext ) {

		$this->fallbackSearchEngine->prefix = $this->prefix;
		$this->fallbackSearchEngine->namespaces = $this->namespaces;

		// #4022
		// https://github.com/wikimedia/mediawiki/commit/a1731db5abba7323eada8c11db2340cf0ecc3670
		if ( method_exists( $this->fallbackSearchEngine, 'transformSearchTerm' ) ) {
			$term = $this->fallbackSearchEngine->transformSearchTerm( $term );
		}

		$term = $this->fallbackSearchEngine->replacePrefixes(
			$term
		);

		if ( $fulltext ) {
			return $this->fallbackSearchEngine->searchText( $term );
		}

		return $this->fallbackSearchEngine->searchTitle( $term );
	}

}
