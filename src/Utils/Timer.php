<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Timer {

	/**
	 * @var float|integer
	 */
	private static $start = array();

	/**
	 * @since 2.5
	 */
	public static function start( $name ) {
		self::$start[$name] = microtime( true );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|null $round
	 *
	 * @return float|integer
	 */
	public static function getElapsedTime( $name, $round = null ) {

		if ( !isset( self::$start[$name] ) ) {
			return 0;
		}

		$time = microtime( true ) - self::$start[$name];

		if ( $round === null  ) {
			return $time;
		}

		return round( $time, $round );
	}

}
