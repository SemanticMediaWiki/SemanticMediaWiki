<?php

namespace SMW\MediaWiki\Connection;

/**
 * https://phabricator.wikimedia.org/T147550
 *
 * The contract of the Database interface has changed in MW 1.28 and introduced
 * incompatibilities which this class tries to bypass.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class OptionsBuilder {

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public static function toString( array $options ) {

		$string = '';

		if ( isset( $options['GROUP BY'] ) ) {
			$string .= ' GROUP BY ' . ( is_array( $options['GROUP BY'] ) ? implode( ',', $options['GROUP BY'] ) : $options['GROUP BY'] );
		}

		$string .= self::makeOrderBy( $options );

		if ( isset( $options['LIMIT'] ) ) {
			$string .= ' LIMIT ' . $options['LIMIT'];
		}

		if ( isset( $options['OFFSET'] ) ) {
			$string .= ' OFFSET ' . $options['OFFSET'];
		}

		return $string;
	}

	/**
	 * @see Database::makeSelectOptions
	 */
	public static function makeSelectOptions( Database $connection, $options ) {
		$preLimitTail = $postLimitTail = '';
		$startOpts = '';

		$noKeyOptions = [];

		foreach ( $options as $key => $option ) {
			if ( is_numeric( $key ) ) {
				$noKeyOptions[$option] = true;
			}
		}

		$preLimitTail .= self::makeGroupByWithHaving( $connection, $options );

		$preLimitTail .= self::makeOrderBy( $options );

		// if (isset($options['LIMIT'])) {
		// 	$tailOpts .= $this->limitResult('', $options['LIMIT'],
		// 		isset($options['OFFSET']) ? $options['OFFSET']
		// 		: false);
		// }

		if ( isset( $noKeyOptions['FOR UPDATE'] ) ) {
			$postLimitTail .= ' FOR UPDATE';
		}

		if ( isset( $noKeyOptions['LOCK IN SHARE MODE'] ) ) {
			$postLimitTail .= ' LOCK IN SHARE MODE';
		}

		if ( isset( $noKeyOptions['DISTINCT'] ) || isset( $noKeyOptions['DISTINCTROW'] ) ) {
			$startOpts .= 'DISTINCT';
		}

		# Various MySQL extensions
		if ( isset( $noKeyOptions['STRAIGHT_JOIN'] ) ) {
			$startOpts .= ' /*! STRAIGHT_JOIN */';
		}

		if ( isset( $noKeyOptions['HIGH_PRIORITY'] ) ) {
			$startOpts .= ' HIGH_PRIORITY';
		}

		if ( isset( $noKeyOptions['SQL_BIG_RESULT'] ) ) {
			$startOpts .= ' SQL_BIG_RESULT';
		}

		if ( isset( $noKeyOptions['SQL_BUFFER_RESULT'] ) ) {
			$startOpts .= ' SQL_BUFFER_RESULT';
		}

		if ( isset( $noKeyOptions['SQL_SMALL_RESULT'] ) ) {
			$startOpts .= ' SQL_SMALL_RESULT';
		}

		if ( isset( $noKeyOptions['SQL_CALC_FOUND_ROWS'] ) ) {
			$startOpts .= ' SQL_CALC_FOUND_ROWS';
		}

		if ( isset( $noKeyOptions['SQL_CACHE'] ) ) {
			$startOpts .= ' SQL_CACHE';
		}

		if ( isset( $noKeyOptions['SQL_NO_CACHE'] ) ) {
			$startOpts .= ' SQL_NO_CACHE';
		}

		$useIndex = '';
		$ignoreIndex = '';

		return [ $startOpts, $useIndex, $preLimitTail, $postLimitTail, $ignoreIndex ];
	}

	protected static function makeGroupByWithHaving( $connection, $options ) {
		$sql = '';

		if ( isset( $options['GROUP BY'] ) ) {
			$gb = is_array( $options['GROUP BY'] )
				? implode( ',', $options['GROUP BY'] )
				: $options['GROUP BY'];
			$sql .= ' GROUP BY ' . $gb;
		}

		if ( isset( $options['HAVING'] ) ) {
			$having = is_array( $options['HAVING'] )
				? $connection->makeList( $options['HAVING'], self::LIST_AND )
				: $options['HAVING'];
			$sql .= ' HAVING ' . $having;
		}

		return $sql;
	}

	protected static function makeOrderBy( $options ) {
		if ( isset( $options['ORDER BY'] ) ) {
			$ob = is_array( $options['ORDER BY'] )
				? implode( ',', $options['ORDER BY'] )
				: $options['ORDER BY'];

			return ' ORDER BY ' . $ob;
		}

		return '';
	}

}
