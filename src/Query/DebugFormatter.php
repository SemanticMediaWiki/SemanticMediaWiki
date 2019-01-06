<?php

namespace SMW\Query;

use SMW\ProcessingErrorMsgHandler;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class DebugFormatter {

	const JSON_FORMAT = 'json';

	/**
	 * @var boolean
	 */
	private static $explainFormat = '';

	/**
	 * @since 3.0
	 *
	 * @param string $explainFormat
	 */
	public static function setExplainFormat( $explainFormat ) {
		if ( $explainFormat === self::JSON_FORMAT ) {
			self::$explainFormat = $explainFormat;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function getFormat( $type ) {

		$format = '';

		// Use a more expressive explain output
		// https://dev.mysql.com/doc/refman/5.6/en/explain.html
		// https://mariadb.com/kb/en/mariadb/explain-formatjson-in-mysql/
		if ( $type === 'mysql' && self::$explainFormat === self::JSON_FORMAT ) {
			$format = 'FORMAT=json';
		}

		return $format;
	}

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
	public static function getStringFrom( $storeName, array $entries, Query $query = null ) {

		if ( $query instanceof Query ) {
			$preEntries = [];
			$preEntries['ASK Query'] = '<div class="smwpre">' . str_replace( '[', '&#91;', $query->getDescription()->getQueryString() ) . '</div>';
			$entries = array_merge( $preEntries, $entries );
			$entries['Query Metrics'] = 'Query-Size:' . $query->getDescription()->getSize() . '<br />' .
						'Query-Depth:' . $query->getDescription()->getDepth();
			$errors = '';

			$queryErrors = ProcessingErrorMsgHandler::normalizeAndDecodeMessages(
				$query->getErrors()
			);

			foreach ( $queryErrors as $error ) {
				$errors .= $error . '<br />';
			}

			if ( $errors === '' ) {
				$errors = 'None';
			}

			$entries['Errors and Warnings'] = $errors;
		}

		$result = '<div class="smw-debug" style="border: 5px dotted #ffcc00; background: #FFF0BD; padding: 20px; margin-bottom: 10px;">' .
		          "<div class='smw-column-header'><big>$storeName debug output</big></div>";

		foreach ( $entries as $header => $information ) {
			$result .= "<div class='smw-column-header'>$header</div>";

			if ( $information !== '' ) {
				$result .= "$information";
			}
		}

		$result .= '</div>';

		return $result;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 * @param array $rows
	 *
	 * @return string
	 */
	public static function prettifyExplain( $type, $res ) {

		$output = '';

		// https://dev.mysql.com/doc/refman/5.0/en/explain-output.html
		if ( $type === 'mysql' ) {
			$output .= '<div class="smwpre" style="word-break:normal;">' .
			'<table class="" style="border-spacing: 5px;"><tr>' .
			'<th style="text-align: left;">ID</th>'.
			'<th style="text-align: left;">select_type</th>'.
			'<th style="text-align: left;">table</th>'.
			'<th style="text-align: left;">type</th>'.
			'<th style="text-align: left;">possible_keys</th>'.
			'<th style="text-align: left;">key</th>'.
			'<th style="text-align: left;">key_len</th>'.
			'<th style="text-align: left;">ref</th>'.
			'<th style="text-align: left;">rows</th>'.
			'<th style="text-align: left;">Extra</th></tr>';

			foreach ( $res as $row ) {

				if ( isset( $row->EXPLAIN ) ) {
					return '<div class="smwpre">' . $row->EXPLAIN . '</div>';
				}

				$output .= "<tr><td>" . $row->id .
				"</td><td>" . $row->select_type .
				"</td><td>" . $row->table .
				"</td><td>" . $row->type  .
				"</td><td>" . $row->possible_keys .
				"</td><td>" . $row->key .
				"</td><td>" . $row->key_len .
				"</td><td>" . $row->ref .
				"</td><td>" . $row->rows .
				"</td><td>" . $row->Extra . "</td></tr>";
			}

			$output .= '</table></div>';
		}

		if ( $type === 'postgres' ) {
			$output .= '<div class="smwpre">';

			foreach ( $res as $row ) {
				foreach ( $row as $key => $value ) {
					$output .= str_replace( [ ' ', '->' ], [ '&nbsp;', '└── ' ], $value ) .'<br>';
				}
			}

			$output .= '</div>';
		}

		// SQlite doesn't support this
		if ( $type === 'sqlite' ) {
			$output .= 'Not supported.';
		}

		return $output;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $sparql
	 *
	 * @return string
	 */
	public static function prettifySparql( $sparql ) {

		$sparql =  str_replace(
			[
				'[',
				':',
				' ',
				'<',
				'>'
			],
			[
				'&#91;',
				'&#x003A;',
				'&#x0020;',
				'&#x3C;',
				'&#x3E;'
			],
			$sparql
		);

		return '<div class="smwpre">' . $sparql . '</div>';
	}

	/**
	 * @since 2.5
	 *
	 * @param string $sql
	 * @param string $alias
	 *
	 * @return string
	 */
	public static function prettifySql( $sql, $alias ) {

		$sql = str_replace(
			[
				"SELECT DISTINCT",
				"FROM",
				"INNER JOIN",
				"LEFT OUTER JOIN",
				"LEFT JOIN",
				"RIGHT JOIN",
				"WHERE",
				"ORDER BY",
				"GROUP BY",
				"LIMIT",
				"OFFSET",
				"AND $alias.smw_",
				",$alias.smw_",
				"AND (",
				"))",
				"(("
			],
			[
				"SELECT DISTINCT<br>&nbsp;",
				"<br>FROM<br>&nbsp;",
				"<br>INNER JOIN<br>&nbsp;",
				"<br>LEFT OUTER JOIN<br>&nbsp;",
				"<br>LEFT JOIN<br>&nbsp;",
				"<br>RIGHT JOIN<br>&nbsp;",
				"<br>WHERE<br>&nbsp;",
				"<br>ORDER BY<br>&nbsp;",
				"<br>GROUP BY<br>&nbsp;",
				"<br>LIMIT<br>&nbsp;",
				"<br>OFFSET<br>&nbsp;",
				"<br>&nbsp;&nbsp;AND $alias.smw_",
				",<br>&nbsp;&nbsp;$alias.smw_",
				"<br>&nbsp;&nbsp;&nbsp;AND (",
				")<br>&nbsp;&nbsp;)",
				"(<br>&nbsp;&nbsp;&nbsp;("
			],
			$sql
		);

		return '<div class="smwpre">' . $sql . '</div>';
	}

}
