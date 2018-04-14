<?php

namespace SMW\MediaWiki\Search;

use SMW\DIWikiPage;

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

	public function __construct( \SMWQueryResult $result, $count = null ) {
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
			$searchResult = SearchResult::newFromTitle( $page->getTitle() );
		}

		// Attempt to use excerpts available from a different back-end
		if ( $searchResult && $this->excerpts !== null ) {
			$this->excerpts->noHighlight();

			if ( ( $excerpt = $this->excerpts->getExcerpt( $page ) ) !== false ) {
				$searchResult->setExcerpt( $excerpt );
			}
		}

		next( $this->pages );

		return $searchResult;
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
			return array();
		}

		// Will cause the highlighter to match every line start, thus returning the first few lines of found pages.
		return array( '^' );
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
			$tokens[] = "\b$key\b";
		}

		return $tokens;
	}

}
