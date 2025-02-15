<?php

namespace SMW\Utils;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class Timer {

	/**
	 * @var float|int
	 */
	private static $start = [];

	/**
	 * @var float|int
	 */
	private $times = [];

	/**
	 * @var string[]
	 */
	public array $keys;

	/**
	 * @since 3.0
	 *
	 * @param int $outputType
	 * @param int $ts
	 *
	 * @return string|bool
	 */
	public static function getTimestamp( $outputType = TS_UNIX, $ts = 0 ) {
		return wfTimestamp( $outputType, $ts );
	}

	/**
	 * @since 2.5
	 */
	public static function start( $name ) {
		self::$start[$name] = microtime( true );
	}

	/**
	 * @since 3.2
	 */
	public function new( $name ) {
		$this->times[$name] = microtime( true );
	}

	/**
	 * @since 3.2
	 */
	public function stop( $name ) {
		if ( isset( $this->times[$name] ) ) {
			$this->times[$name] = microtime( true ) - $this->times[$name];
		}

		return $this;
	}

	/**
	 * @since 3.2
	 */
	public function getTimes() {
		return $this->times;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $name
	 * @param int|null $round
	 *
	 * @return float|int
	 */
	public static function getElapsedTime( $name, $round = null ) {
		if ( !isset( self::$start[$name] ) ) {
			return 0;
		}

		$time = microtime( true ) - self::$start[$name];

		if ( $round === null ) {
			return $time;
		}

		return round( $time, $round );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 * @param int|null $round
	 *
	 * @return string
	 */
	public static function getElapsedTimeAsLoggableMessage( $name, $round = null ) {
		return $name . ' (procTime in sec: ' . self::getElapsedTime( $name, $round ) . ')';
	}

}
