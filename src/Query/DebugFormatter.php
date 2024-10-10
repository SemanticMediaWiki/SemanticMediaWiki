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
	 * @var string
	 */
	private $type = '';

	/**
	 * @var string
	 */
	private $format = '';

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @since 3.0
	 *
	 * @param string|null $type
	 * @param string $format
	 */
	public function __construct( ?string $type = '', string $format = '' ) {
		$this->type = $type;

		// Use a more expressive explain output
		// https://dev.mysql.com/doc/refman/5.6/en/explain.html
		// https://mariadb.com/kb/en/mariadb/explain-formatjson-in-mysql/
		if ( $type === 'mysql' && $format === self::JSON_FORMAT ) {
			$this->format = 'FORMAT=json';
		}
	}

	/**
	 * @since 3.2
	 *
	 * @param string $name
	 */
	public function setName( string $name ) {
		$this->name = $name;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getFormat(): string {
		return $this->format;
	}

	/**
	 * Generate textual debug output that shows an arbitrary list of informative
	 * fields. Used for formatting query debug output.
	 *
	 * @note All strings given must be usable and safe in wiki and HTML
	 * contexts.
	 *
	 * @param $entries array of name => value of informative entries to display
	 * @param $query SMWQuery or null, if given add basic data about this query as well
	 *
	 * @return string
	 */
	public function buildHTML( array $entries, Query $query = null ) {
		if ( $query instanceof Query ) {
			$preEntries = [];
			$description = $query->getDescription();
			$queryString = str_replace( '[', '&#91;', $description->getQueryString() ?? '' );

			$preEntries['ASK Query'] = '<div class="smwpre">' . $queryString . '</div>';
			$entries = array_merge( $preEntries, $entries );
			$entries['Query Metrics'] = 'Query-Size:' . $description->getSize() . '<br />' .
						'Query-Depth:' . $description->getDepth();
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

		$style = '';
		$result = '<div class="smw-debug-box">' .
		          "<div class='smw-column-header'><big>Debug output <span style='float:right'>$this->name</span></big></div>";

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
	 * @param array $rows
	 *
	 * @return string
	 */
	public function prettifyExplain( iterable $res ) {
		$output = '';

		// https://dev.mysql.com/doc/refman/5.0/en/explain-output.html
		if ( $this->type === 'mysql' ) {
			$output .= '<div class="smwpre" style="word-break:normal;">' .
			'<table class="" style="border-spacing: 5px;"><tr>' .
			'<th style="text-align: left;">ID</th>' .
			'<th style="text-align: left;">select_type</th>' .
			'<th style="text-align: left;">table</th>' .
			'<th style="text-align: left;">type</th>' .
			'<th style="text-align: left;">possible_keys</th>' .
			'<th style="text-align: left;">key</th>' .
			'<th style="text-align: left;">key_len</th>' .
			'<th style="text-align: left;">ref</th>' .
			'<th style="text-align: left;">rows</th>' .
			'<th style="text-align: left;">filtered</th>' .
			'<th style="text-align: left;">Extra</th></tr>';

			foreach ( $res as $row ) {

				if ( isset( $row->EXPLAIN ) ) {
					return '<div class="smwpre">' . $row->EXPLAIN . '</div>';
				}

				$possible_keys = $row->possible_keys;
				$ref = $row->ref;

				if ( strpos( $possible_keys, ',' ) !== false ) {
					$possible_keys = implode( ', ', explode( ',', $possible_keys ) );
				}

				if ( strpos( $ref ?? '', ',' ) !== false ) {
					$ref = implode( ', ', explode( ',', $ref ) );
				}

				$output .= "<tr style='vertical-align: top;'><td>" . $row->id .
				"</td><td>" . $row->select_type .
				"</td><td>" . $row->table .
				"</td><td>" . $row->type .
				"</td><td>" . $possible_keys .
				"</td><td>" . $row->key .
				"</td><td>" . $row->key_len .
				"</td><td>" . $ref .
				"</td><td>" . $row->rows .
				"</td><td>" . ( $row->filtered ?? '' ) .
				"</td><td>" . $row->Extra . "</td></tr>";
			}

			$output .= '</table></div>';
		}

		if ( $this->type === 'postgres' ) {
			$output .= '<div class="smwpre">';

			foreach ( $res as $row ) {
				foreach ( $row as $key => $value ) {
					$output .= str_replace( [ ' ', '->' ], [ '&nbsp;', '└── ' ], $value ) . '<br>';
				}
			}

			$output .= '</div>';
		}

		if ( $this->type === 'sqlite' ) {
			$output .= 'QUERY PLAN' . "<br>";
			$plan = '';
			$last = count( $res ) - 1;

			foreach ( $res as $k => $row ) {

				// https://www.sqlite.org/eqp.html notes "... output format did change
				// substantially with the version 3.24.0 release ..."
				if ( !isset( $row->id ) ) {
					continue;
				}

				$marker = $k === $last ? '└──' : '├──';
				$plan .= "<div style='margin-left:15px;'>$marker [" . $row->id . '] `' . $row->detail . "`</div>";
			}

			if ( $plan === '' ) {
				$plan = 'The SQLite version doesn\'t support a query plan.';
			}

			$output .= $plan;
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
	public function prettifySPARQL( $sparql ) {
		$sparql = str_replace(
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
			$sparql ?? ''
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
	public function prettifySQL( $sql, $alias ) {
		$matches = [];
		$i = 0;

		$sql = preg_replace_callback( '/NOT IN .*\)/', function ( $m ) use ( &$matches, &$i ) {
			$i++;

			$string = str_replace( [ 'AND ((' ], [ "AND (<br>   (" ], $m[0] );

			$matches["not_int$i"] = $string;
			return "not_int$i";
		}, $sql );

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

		foreach ( $matches as $key => $value ) {
			$sql = str_replace( $key, $value, $sql );
		}

		return '<div class="smwpre">' . $sql . '</div>';
	}

}
