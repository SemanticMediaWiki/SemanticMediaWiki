<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class StatsFormatter {

	/**
	 * Stats as plain string
	 */
	const FORMAT_PLAIN = 'plain';

	/**
	 * Stats as JSON output
	 */
	const FORMAT_JSON = 'json';

	/**
	 * Stats as HTML list output
	 */
	const FORMAT_HTML = 'html';

	/**
	 * @since 2.5
	 *
	 * @param array $stats
	 * @param string|null $format
	 *
	 * @return string|array
	 */
	public static function format( array $stats, $format = null ) {

		$output = '';

		if ( $format === self::FORMAT_PLAIN ) {
			foreach ( $stats as $key => $value ) {
				$output .= '- ' . $key . "\n";

				if ( !is_array( $value ) ) {
					continue;
				}

				foreach ( $value as $k => $v ) {
					$output .= '  - ' . $k . ': ' . $v . "\n";
				}
			}
		}

		if ( $format === self::FORMAT_HTML ) {
			$output .= '<ul>';
			foreach ( $stats as $key => $value ) {
				$output .= '<li>' . $key . '<ul>';

				if ( !is_array( $value ) ) {
					continue;
				}

				foreach ( $value as $k => $v ) {
					$output .= '<li>' . $k . ': ' . $v . "</li>";
				}
				$output .= '</ul></li>';
			}
			$output .= '</ul>';
		}

		if ( $format === self::FORMAT_JSON ) {
			$output .= json_encode( $stats, JSON_PRETTY_PRINT );
		}

		if ( $format === null ) {
			$output = $stats;
		}

		return $output;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $stats
	 * @param string $separator
	 *
	 * @return array
	 */
	public static function getStatsFromFlatKey( array $stats, $separator = '.' ) {

		$data = $stats;
		$stats = [];

		foreach ( $data as $key => $value ) {
			if ( strpos( $key, $separator ) !== false ) {
				$stats = array_merge_recursive( $stats, self::stringToArray( $separator, $key, $value ) );
			} else {
				$stats[$key] = $value;
			}
		}

		return $stats;
	}

	// http://stackoverflow.com/questions/10123604/multstatsdIdimensional-array-from-string
	private static function stringToArray( $separator, $path, $value ) {

		$pos = strpos( $path, $separator );

		if ( $pos === false ) {
			return [ $path => $value ];
		}

		$key = substr( $path, 0, $pos );
		$path = substr( $path, $pos + 1 );

		$result = [
			$key => self::stringToArray( $separator, $path, $value )
		];

		return $result;
	}

}
