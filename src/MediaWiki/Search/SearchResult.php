<?php

namespace SMW\MediaWiki\Search;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SearchResult extends \SearchResult {

	/**
	 * @see SearchResult::getSectionTitle
	 */
	function getSectionTitle() {

		if ( !isset( $this->mTitle ) || $this->mTitle->getFragment() === '' ) {
			return null;
		}

		return $this->mTitle;
	}

	/**
	 * Set a text excerpt retrieved from a different back-end.
	 *
	 * @param string $text|null
	 */
	public function setExcerpt( $text = null ) {
		$this->mText = $text;
	}

	/**
	 * @return string|null
	 */
	public function getExcerpt() {
		return $this->mText;
	}

	/**
	 * @see SearchResult::getSectionTitle
	 */
	public function getTitleSnippet() {

		// Extend the title preferably using the DISPLAYTITLE if available!

		return '';
	}

}
