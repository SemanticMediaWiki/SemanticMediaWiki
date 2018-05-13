<?php

namespace SMW\Elastic\QueryEngine;

use SMW\DIWikiPage;
use SMW\Query\Excerpts as QExcerpts;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Excerpts extends QExcerpts {

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
				return is_string( $map[1] ) ? $map[1] : $this->format( $map[1] );
			}
		}

		return false;
	}

	private function format( $v ) {
		$text = '';

		foreach ( $v as $key => $value ) {
			$text .= implode( ' ', $value ) ;
		}

		if ( $this->noHighlight ) {
			$text = str_replace( [ '<em>', '</em>', "\n" ], [ '', '', ' ' ], $text );
		}

		return $text;
	}

}
