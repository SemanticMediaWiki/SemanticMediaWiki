<?php

namespace SMW\Elastic\Indexer;

use SMW\Parser\LinksEncoder;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class TextSanitizer {

	/**
	 * Remove anything that resembles [[:...|foo]] to avoid distracting the indexer
	 * with internal links annotation that are not relevant.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function removeLinks( $text ) : string {

		if ( $text === '' ) {
			return $text;
		}

		// [[Has foo::Bar]]
		$text = LinksEncoder::removeAnnotation( $text );

		// {{DEFAULTSORT: ... }}
		$text = preg_replace( "/\\{\\{([^|]+?)\\}\\}/", "", $text );

		// Removed too much ...
		//	$text = preg_replace( '/\\[\\[[\s\S]+?::/', '[[', $text );

		// [[:foo|bar]]
		$text = preg_replace( '/\\[\\[:[^|]+?\\|/', '[[', $text );
		$text = preg_replace( "/\\{\\{([^|]+\\|)(.*?)\\}\\}/", "\\2", $text );
		$text = preg_replace( "/\\[\\[([^|]+?)\\]\\]/", "\\1", $text );

		return $text;
	}

}
