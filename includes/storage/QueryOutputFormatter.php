<?php

namespace SMW;

use SMWQuery as Query;

/**
 * @ingroup Store
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 */
class QueryOutputFormatter {

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
	public static function formatDebugOutput( $storeName, array $entries, Query $query = null ) {

		if ( $query instanceOf Query ) {
			$preEntries = array();
			$preEntries['Generated Wiki-Query'] = '<pre>' . str_replace( '[', '&#x005B;', $query->getDescription()->getQueryString() ) . '</pre>';
			$preEntries['Query Metrics'] = 'Query-Size:' . $query->getDescription()->getSize() . '<br />' .
						'Query-Depth:' . $query->getDescription()->getDepth();
			$entries = array_merge( $preEntries, $entries );

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
