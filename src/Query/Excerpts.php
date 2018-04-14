<?php

namespace SMW\Query;

use SMW\DIWikiPage;

/**
 * Record excerpts for query results that support an excerpt retrieval function.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Excerpts {

	/**
	 * @var []
	 */
	protected $excerpts = [];

	/**
	 * @var boolean
	 */
	protected $noHighlight = false;

	/**
	 * @since 3.0
	 */
	public function noHighlight() {
		$this->noHighlight = true;
	}

	/**
	 * @note The hash is expected to match DIWikiPage::getHash to easily match
	 * result subjects available in an QueryResult instance.
	 *
	 * @since 3.0
	 *
	 * @param DIWikiPage|string $hash
	 * @param string|integer $score
	 */
	public function addExcerpt( $hash, $excerpt ) {

		if ( $hash instanceof DIWikiPage ) {
			$hash = $hash->getHash();
		}

		$this->excerpts[] = [ $hash, $excerpt ];
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWIkiPage|string $hash
	 *
	 * @return string|integer|false
	 */
	public function getExcerpt( $hash ) {

		if ( $hash instanceof DIWikiPage ) {
			$hash = $hash->getHash();
		}

		foreach ( $this->excerpts as $map ) {
			if ( $map[0] === $hash ) {
				return $map[1];
			}
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getExcerpts() {
		return $this->excerpts;
	}

}
