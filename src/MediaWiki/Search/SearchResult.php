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

}
