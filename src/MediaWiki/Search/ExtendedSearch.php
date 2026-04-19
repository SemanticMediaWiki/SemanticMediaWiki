<?php

namespace SMW\MediaWiki\Search;

use SearchEngine;
use SearchSuggestionSet;
use SMW\Formatters\InfoLink;
use SMW\Query\Query;
use SMW\Query\QueryResult;
use SMW\Store;

/**
 * Search engine that will try to find wiki pages by interpreting the search
 * term as an SMW query.
 *
 * If successful, the pages according to the query will be returned.
 * If not it falls back to the default search engine.
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author  Stephan Gambke
 */
class ExtendedSearch {

	/**
	 * To provide a wider search radius for the completion search
	 */
	const COMPLETION_SEARCH_EXTRA_SEARCH_SIZE = 10;

	private array $errors = [];

	private ?QueryBuilder $queryBuilder = null;

	private string $queryString = '';

	private ?InfoLink $queryLink = null;

	private string $prefix = '';

	private $extraPrefixMap = [];

	private array $namespaces = [];

	private array $searchableNamespaces = [];

	private int $limit = 10;

	private int $offset = 0;

	private string $completionSearchTerm = '';

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly Store $store,
		private readonly SearchEngine $fallbackSearchEngine,
	) {
	}

	/**
	 * @since 3.1
	 */
	public function setExtraPrefixMap( array $extraPrefixMap ): void {
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
	 */
	public function setQueryBuilder( QueryBuilder $queryBuilder ): void {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 3.1
	 */
	public function setPrefix( string $prefix ): void {
		$this->prefix = $prefix;
	}

	/**
	 * @since 3.1
	 */
	public function setNamespaces( array $namespaces ): void {
		$this->namespaces = $namespaces;
	}

	/**
	 * @since 3.1
	 */
	public function setSearchableNamespaces( array $searchableNamespaces ): void {
		$this->searchableNamespaces = $searchableNamespaces;
	}

	/**
	 * @since 3.1
	 *
	 * @param int $limit
	 * @param int $offset
	 */
	public function setLimitOffset( $limit, $offset = 0 ): void {
		$this->limit = intval( $limit );
		$this->offset = intval( $offset );
	}

	/**
	 * @since 3.2
	 */
	public function setCompletionSearchTerm( string $completionSearchTerm ): void {
		$this->completionSearchTerm = $completionSearchTerm;
	}

	/**
	 * @since 3.1
	 */
	public function getLimit(): int {
		return $this->limit;
	}

	/**
	 * @since 3.1
	 */
	public function getOffset(): int {
		return $this->offset;
	}

	/**
	 * @since 3.0
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 */
	public function getQueryString(): string {
		return $this->queryString;
	}

	/**
	 * @since 3.0
	 */
	public function getQueryLink(): ?InfoLink {
		return $this->queryLink;
	}

	/**
	 * @since 3.0
	 */
	public function getValidSorts(): array {
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

		// Avoid MW's auto formatting of title entities
		if ( $search !== '' ) {
			$search[0] = strtolower( $search[0] );
		}

		if ( $this->hasPrefixAndMinLenForCompletionSearch( $search ) ) {
			if ( $this->getSearchQuery( $search ) !== null ) {
				// Lets widen the search in case we fetch subobjects
				$this->limit += self::COMPLETION_SEARCH_EXTRA_SEARCH_SIZE;
				$searchResultSet = $this->newSearchResultSet( $search, false, false );
			}

			if ( $searchResultSet instanceof SearchResultSet ) {
				return $searchResultSet->newSearchSuggestionSet();
			}
		}

		// #4342
		//
		// Allow the fallback to work with the "raw" (not normalized) search term
		if ( trim( $search ) === '' ) {
			$search = $this->completionSearchTerm;
		}

		return $this->fallbackSearchEngine->completionSearch( $search );
	}

	private function hasPrefixAndMinLenForCompletionSearch( $search ): bool {
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

	private function newSearchResultSet( $term, bool $count = true, bool $highlight = true ): ?SearchResultSet {
		$query = $this->getSearchQuery( $term );

		if ( $query === null ) {
			return null;
		}

		$query->setOffset( $this->offset );
		$query->setLimit( $this->limit, false );
		$this->queryString = $query->getQueryString();

		$query->clearErrors();
		$query->setOption( 'highlight.fragment', $highlight );
		$query->setOption( Query::PROC_CONTEXT, 'SpecialSearch' );

		$result = $this->store->getQueryResult( $query );
		$this->errors = $query->getErrors();

		$this->queryLink = $result->getQueryLink();
		$this->queryLink->setParameter( $this->offset, 'offset' );
		$this->queryLink->setParameter( $this->limit, 'limit' );

		if ( $count ) {
			$query->querymode = Query::MODE_COUNT;
			$query->setOffset( 0 );

			$queryResult = $this->store->getQueryResult( $query );
			$count = $queryResult instanceof QueryResult ? $queryResult->getCountValue() : $queryResult;
		} else {
			$count = 0;
		}

		return new SearchResultSet( $result, $count );
	}

	private function getSearchQuery( string $term ): ?Query {
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

	private function searchFallbackSearchEngine( $term, bool $fulltext ) {
		$this->fallbackSearchEngine->prefix = $this->prefix;
		$this->fallbackSearchEngine->namespaces = $this->namespaces;

		if ( $fulltext ) {
			return $this->fallbackSearchEngine->searchText( $term );
		}

		return $this->fallbackSearchEngine->searchTitle( $term );
	}

}
