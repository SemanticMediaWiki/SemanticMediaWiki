<?php

namespace SMW\Query;

use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 */
class DebugOutputFormatter {

	/**
	 * Generate textual debug output that shows an arbitrary list of informative
	 * fields. Used for formatting query debug output.
	 *
	 * @note All strings given must be usable and safe in wiki and HTML
	 * contexts.
	 *
	 * @param $storeName string name of the storage backend for which this is generated
	 * @param $entries array of name => value of informative entries to display
	 * @param $query SMWQuery or null, if given add basic data about this query as well
	 *
	 * @return string
	 */
	public static function formatOutputFor( $storeName, array $entries, Query $query = null ) {

		if ( $query instanceof Query ) {
			$preEntries = array();
			$preEntries['Ask query'] = '<div class="smwpre">' . str_replace( '[', '&#x005B;', $query->getDescription()->getQueryString() ) . '</div>';
			$entries = array_merge( $preEntries, $entries );
			$entries['Query Metrics'] = 'Query-Size:' . $query->getDescription()->getSize() . '<br />' .
						'Query-Depth:' . $query->getDescription()->getDepth();
			$errors = '';

			foreach ( $query->getErrors() as $error ) {
				$errors .= $error . '<br />';
			}

			if ( $errors === '' ) {
				$errors = 'None';
			}

			$entries['Errors and Warnings'] = $errors;
		}

		$result = '<div style="border: 5px dotted #A1FB00; background: #FFF0BD; padding: 20px; ">' .
		          "<h3>Debug Output by $storeName</h3>";

		foreach ( $entries as $header => $information ) {
			$result .= "<h4>$header</h4>";

			if ( $information !== '' ) {
				$result .= "$information";
			}
		}

		$result .= '</div>';

		return $result;
	}

}
