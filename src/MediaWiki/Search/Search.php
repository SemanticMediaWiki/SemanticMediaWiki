<?php

namespace SMW\MediaWiki\Search;

use Content;
use DatabaseBase;
use RuntimeException;
use SearchEngine;
use SMW\ApplicationFactory;
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
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author  Stephan Gambke
 */
class Search extends SearchEngine {

	private $fallbackSearch = null;

	private $database = null;

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
	 * @see SearchEngine::getValidSorts
	 *
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
	 * @param null|SearchEngine $fallbackSearch
	 */
	public function setFallbackSearchEngine( SearchEngine $fallbackSearch = null ) {
		$this->fallbackSearch = $fallbackSearch;
	}

	/**
	 * @param $type
	 */
	private function assertValidFallbackSearchEngineType( $type ) {

		if ( !class_exists( $type ) ) {
			throw new RuntimeException( "$type does not exist." );
		}

		if ( $type === 'SMWSearch' ) {
			throw new RuntimeException( 'SMWSearch is not a valid fallback search engine type.' );
		}

		if ( $type !== 'SearchEngine' && !is_subclass_of( $type, 'SearchEngine' ) ) {
			throw new RuntimeException( "$type is not a valid fallback search engine type." );
		}
	}

	/**
	 * @return SearchEngine
	 */
	public function getFallbackSearchEngine() {

		if ( $this->fallbackSearch === null ) {

			$type = ApplicationFactory::getInstance()->getSettings()->get( 'smwgFallbackSearchType' );

			$dbr = $this->getDB();

			if ( $type === null ) {
				$type = ApplicationFactory::getInstance()->create( 'DefaultSearchEngineTypeForDB', $dbr );
			}

			$this->assertValidFallbackSearchEngineType( $type );

			$this->fallbackSearch = new $type( $dbr );
		}

		return $this->fallbackSearch;
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
	 * @param DatabaseBase $connection
	 */
	public function setDB( DatabaseBase $connection ) {
		$this->database = $connection;
		$this->fallbackSearch = null;
	}

	/**
	 * @return \IDatabase
	 */
	public function getDB() {

		if ( $this->database === null ) {
			$this->database = ApplicationFactory::getInstance()->getLoadBalancer()->getConnection( defined( 'DB_REPLICA' ) ? DB_REPLICA : DB_SLAVE );
		}

		return $this->database;
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
			ApplicationFactory::getInstance()->getStore(),
			$term
		);

		$query = $this->queryBuilder->getQuery(
			$this->queryString
		);

		$this->queryBuilder->addSort( $query );

		$this->queryBuilder->addNamespaceCondition(
			$query,
			$this->searchableNamespaces()
		);

		return $query;
	}

	private function searchFallbackSearchEngine( $term, $fulltext ) {

		$f = $this->getFallbackSearchEngine();
		$f->prefix = $this->prefix;
		$f->namespaces = $this->namespaces;

		$term = $f->transformSearchTerm( $term );
		$term = $f->replacePrefixes( $term );

		return $fulltext ? $f->searchText( $term ) : $f->searchTitle( $term );
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
	 * @see SearchEngine::completionSearchBackend
	 *
	 * Perform a completion search.
	 *
	 * @param string $search
	 *
	 * @return SearchSuggestionSet
	 */
	protected function completionSearchBackend( $search ) {

		$searchResultSet = null;

		// Avoid MW's auto formatting of title entities
		if ( $search !== '' ) {
			$search{0} = strtolower( $search{0} );
		}

		$searchEngine = $this->getFallbackSearchEngine();

		if ( !$this->hasPrefixAndMinLenForCompletionSearch( $search, 3 ) ) {
			return $searchEngine->completionSearch( $search );
		}

		if ( $this->getSearchQuery( $search ) !== null ) {
			$searchResultSet = $this->newSearchResultSet( $search, false, false );
		}

		if ( $searchResultSet instanceof SearchResultSet ) {
			return $searchResultSet->newSearchSuggestionSet();
		}

		return $searchEngine->completionSearch( $search );
	}

	private function hasPrefixAndMinLenForCompletionSearch( $term, $minLen ) {

		// Only act on when `in:foo`, `has:SomeProperty`, or `phrase:some text`
		// is actively used as prefix

		if ( strpos( $term, 'in:' ) !== false && mb_strlen( $term ) >= ( 3 + $minLen ) ) {
			return true;
		}

		if ( strpos( $term, 'has:' ) !== false && mb_strlen( $term ) >= ( 4 + $minLen ) ) {
			return true;
		}

		if ( strpos( $term, 'phrase:' ) !== false && mb_strlen( $term ) >= ( 7 + $minLen ) ) {
			return true;
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

		$store = ApplicationFactory::getInstance()->getStore();
		$query->clearErrors();
		$query->setOption( 'highlight.fragment', $highlight );

		$result = $store->getQueryResult( $query );
		$this->errors = $query->getErrors();
		$this->queryLink = $result->getQueryLink();
		$this->queryLink->setParameter( $this->offset, 'offset' );
		$this->queryLink->setParameter( $this->limit, 'limit' );

		if ( $count ) {
			$query->querymode = SMWQuery::MODE_COUNT;
			$query->setOffset( 0 );

			$queryResult = $store->getQueryResult( $query );
			$count = $queryResult instanceof QueryResult ? $queryResult->getCountValue() : $queryResult;
		} else {
			$count = 0;
		}

		return new SearchResultSet( $result, $count );
	}

	/**
	 * @param string $feature
	 *
	 * @return bool
	 */
	public function supports( $feature ) {
		return $this->getFallbackSearchEngine()->supports( $feature );
	}

	/**
	 * May performs database-specific conversions on text to be used for
	 * searching or updating search index.
	 *
	 * @param string $string String to process
	 *
	 * @return string
	 */
	public function normalizeText( $string ) {
		return $this->getFallbackSearchEngine()->normalizeText( $string );
	}

	public function getTextFromContent( Title $t, Content $c = null ) {
		return $this->getFallbackSearchEngine()->getTextFromContent( $t, $c );
	}

	public function textAlreadyUpdatedForIndex() {
		return $this->getFallbackSearchEngine()->textAlreadyUpdatedForIndex();
	}

	/**
	 * Create or update the search index record for the given page.
	 * Title and text should be pre-processed.
	 *
	 * @param int    $id
	 * @param string $title
	 * @param string $text
	 */
	public function update( $id, $title, $text ) {
		$this->getFallbackSearchEngine()->update( $id, $title, $text );
	}

	/**
	 * Update a search index record's title only.
	 * Title should be pre-processed.
	 *
	 * @param int    $id
	 * @param string $title
	 */
	public function updateTitle( $id, $title ) {
		$this->getFallbackSearchEngine()->updateTitle( $id, $title );
	}

	/**
	 * Delete an indexed page
	 * Title should be pre-processed.
	 *
	 * @param int    $id    Page id that was deleted
	 * @param string $title Title of page that was deleted
	 */
	public function delete( $id, $title ) {
		$this->getFallbackSearchEngine()->delete( $id, $title );
	}

	public function setFeatureData( $feature, $data ) {
		parent::setFeatureData( $feature, $data );
		$this->getFallbackSearchEngine()->setFeatureData( $feature, $data );
	}

	/**
	 * @param String $feature
	 *
	 * @return array|null
	 */
	public function getFeatureData( $feature ) {

		if ( array_key_exists( $feature, $this->features ) ) {
			return $this->features[$feature];
		}

		return null;
	}

	/**
	 * SMW queries do not have prefixes. Returns query as is.
	 *
	 * @param string $query
	 *
	 * @return string
	 */
	public function replacePrefixes( $query ) {
		return $query;
	}

	/**
	 * No Transformation needed. Returns term as is.
	 * @param $term
	 * @return mixed
	 */
	public function transformSearchTerm( $term ) {
		return $term;
	}

	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * @return boolean
	 */
	public function getShowSuggestion() {
		return $this->showSuggestion;
	}

	public function setLimitOffset( $limit, $offset = 0 ) {
		parent::setLimitOffset( $limit, $offset );
		$this->getFallbackSearchEngine()->setLimitOffset( $limit, $offset );
	}

	public function setNamespaces( $namespaces ) {
		parent::setNamespaces( $namespaces );
		$this->getFallbackSearchEngine()->setNamespaces( $namespaces );
	}

	public function setShowSuggestion( $showSuggestion ) {
		parent::setShowSuggestion( $showSuggestion );
		$this->getFallbackSearchEngine()->setShowSuggestion( $showSuggestion );
	}
}
