<?php

namespace SMW\MediaWiki\Search;

use SMW\DIWikiPage;
use SMW\Utils\CharExaminer;
use SearchSuggestion;
use SearchSuggestionSet;
use SMW\Query\QueryResult;

/**
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since   2.1
 *
 * @author  Stephan Gambke
 */
class SearchResultSet extends \SearchResultSet {

	/**
	 * @var DIWikiPage[]|[]
	 */
	private $pages;

	/**
	 * @var QueryToken
	 */
	private $queryToken;

	/**
	 * @var Excerpts
	 */
	private $excerpts;

	private $count = null;

	public function __construct( QueryResult $result, $count = null ) {
		$this->pages = $result->getResults();
		$this->queryToken = $result->getQuery()->getQueryToken();
		$this->excerpts = $result->getExcerpts();
		$this->count = $count;
	}

	/**
	 * Return number of rows included in this result set.
	 *
	 * @return int|void
	 */
	public function numRows() {
		return count( $this->pages );
	}

	/**
	 * Return true if results are included in this result set.
	 *
	 * @return bool
	 */
	public function hasResults() {
		return $this->numRows() > 0;
	}

	/**
	 * Fetches next search result, or false.
	 *
	 * @return SearchResult
	 */
	public function next() {

		$page = current( $this->pages );
		$searchResult = false;

		if ( $page instanceof DIWikiPage ) {
			$searchResult = new SearchResult( $page->getTitle() );
		}

		// Attempt to use excerpts available from a different back-end
		if ( $searchResult && $this->excerpts !== null ) {
			if ( ( $excerpt = $this->excerpts->getExcerpt( $page ) ) !== false ) {
				$searchResult->setExcerpt( $excerpt, $this->excerpts->hasHighlight() );
			}
		}

		next( $this->pages );

		return $searchResult;
	}

	/**
	 * @since 3.0
	 *
	 * @return SearchSuggestionSet
	 */
	public function newSearchSuggestionSet() {

		$suggestions = [];
		$filter = [];

		$hasMoreResults = false;
		$score = count( $this->pages );

		foreach ( $this->pages as $page ) {
			if ( ( $title = $page->getTitle() ) !== null ) {
				$key = $title->getPrefixedDBKey();

				if ( $title->getNamespace() !== SMW_NS_PROPERTY && !$title->exists() ) {
					continue;
				}

				if ( isset( $filter[$key] ) ) {
					continue;
				}

				// Filter subobjects which are not distinguishable in MW
				$filter[$key] = true;
				$suggestions[] = SearchSuggestion::fromTitle( $score--, $title );
			}
		}

		return new SearchSuggestionSet( $suggestions, $hasMoreResults );
	}

	/**
	 * @see SearchResultSet::extractResults
	 *
	 * @since 3.0
	 */
	public function extractResults() {

		// #3204
		// https://github.com/wikimedia/mediawiki/commit/720fdfa7901cbba93b5695ed5f00f982272ced27
		//
		// MW 1.32+:
		// - Remove SearchResultSet::next, SearchResultSet::numRows
		// - Move QueryResult::getResults, QueryResult::getExcerpts into this
		//   method to avoid constructor work

		if ( $this->pages === [] ) {
			return $this->results = [];
		}

		foreach ( $this->pages as $page ) {

			if ( $page instanceof DIWikiPage ) {
				$searchResult = new SearchResult( $page->getTitle() );
			}

			// Attempt to use excerpts available from a different back-end
			if ( $searchResult && $this->excerpts !== null ) {
				if ( ( $excerpt = $this->excerpts->getExcerpt( $page ) ) !== false ) {
					$searchResult->setExcerpt( $excerpt, $this->excerpts->hasHighlight() );
				}
			}

			$this->results[] = $searchResult;
		}

		return $this->results;
	}

	/**
	 * Returns true, so Special:Search won't offer the user a link to a create
	 * a page named by the search string because the name would contain the
	 * search syntax, i.e. the SMW query.
	 *
	 * @return bool
	 */
	public function searchContainedSyntax() {
		return true;
	}

	public function getTotalHits() {
		return $this->count;
	}

	/**
	 * Return an array of regular expression fragments for matching
	 * the search terms as parsed by the engine in a text extract.
	 *
	 * This is a temporary hack for MW versions that can not cope
	 * with no search term being returned (<1.24).
	 *
	 * @deprecated remove once min supported MW version has \SearchHighlighter::highlightNone()
	 *
	 * @return string[]
	 */
	public function termMatches() {

		if ( ( $tokens = $this->getTokens() ) !== [] ) {
			return $tokens;
		}

		if ( method_exists( '\SearchHighlighter', 'highlightNone' ) ) {
			return [];
		}

		// Will cause the highlighter to match every line start, thus returning the first few lines of found pages.
		return [ '^' ];
	}

	private function getTokens() {

		$tokens = [];

		if ( $this->queryToken === null ) {
			return $tokens;
		}

		// Use tokens gathered from a query context [[in:Foo]] (~~*Foo*), a filter context
		// such as [[Category:Foo]] is not considered eligible to provide a
		// token.
		foreach ( $this->queryToken->getTokens() as $key => $value ) {
			// Avoid add \b boundary checks for CJK where whitespace is not used
			// as word break
			$tokens[] = CharExaminer::isCJK( $key ) ? "$key" : "\b$key\b";
		}

		return $tokens;
	}

}
