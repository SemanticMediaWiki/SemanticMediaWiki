<?php

namespace SMW\Elastic\QueryEngine;

use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Excerpts extends \SMW\Query\Excerpts {

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage|string $hash
	 *
	 * @return string|integer|false
	 */
	public function getExcerpt( $hash ) {

		if ( $hash instanceof DIWikiPage ) {
			$hash = $hash->getHash();
		}

		foreach ( $this->excerpts as $map ) {
			if ( $map[0] === $hash ) {
				return $this->format( $map[1] );
			}
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function hasHighlight() {
		return $this->noHighlight ? false : true;
	}

	private function format( $v ) {

		// https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html
		// By default, highlighted text is wrapped in <em> and </em> tags

		$text = '';

		if ( is_array( $v ) ) {
			foreach ( $v as $key => $value ) {
				$text .= implode( ' ', $value ) ;
			}
		} else {
			$text = $v;
		}

		if ( $this->stripTags ) {
			$text = str_replace(
				[ '<em>', '</em>' ],
				[ '&lt;em&gt;', '&lt;/em&gt;' ],
				$text
			);

			// Remove tags to avoid any output disruption
			$text = strip_tags( $text );

			$text = str_replace(
				[ '&lt;em&gt;', '&lt;/em&gt;' ],
				[ '<em>', '</em>' ],
				$text
			);
		}

		if ( $this->noHighlight ) {
			$text = str_replace( [ '<em>', '</em>', "\n" ], [ '', '', ' ' ], $text );
		}

		return $text;
	}

}
