<?php

namespace SMW\DataValues\Time;

/**
 * Low-level helpers for parsing time and military-time notation from tokens.
 *
 * These methods are extracted from TimeValueParser because PHPStan's
 * IntegerRangeType inference OOMs when parseMilTimeString's integer range
 * tracking (intval + range guards) coexists with the assembly logic in a
 * single class. Keeping them in a separate file isolates the analysis scope.
 *
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 7.0
 */
class TimeStringParser {

	/**
	 * Parse an international time string, e.g. "13:45:23-3:30".
	 *
	 * @return array{hours: int, minutes: int|false, seconds: int|false, timeoffset: int|float|false}|false
	 */
	public static function parseTimeString( string $string ): array|false {
		if ( !preg_match( "/^[T]?([0-2]?[0-9]):([0-5][0-9])(:[0-5][0-9])?(([+\-][0-2]?[0-9])(:(30|00))?)?$/u", $string, $match ) ) {
			return false;
		}

		$nhours = intval( $match[1] );
		$nminutes = $match[2] ? intval( $match[2] ) : false;

		if ( ( count( $match ) > 3 ) && ( $match[3] !== '' ) ) {
			$nseconds = intval( substr( $match[3], 1 ) );
		} else {
			$nseconds = false;
		}

		if ( ( $nhours < 25 ) && ( ( $nhours < 24 ) || ( $nminutes + $nseconds == 0 ) ) ) {
			$timeoffset = false;
			if ( ( count( $match ) > 5 ) && ( $match[5] !== '' ) ) {
				$timeoffset = intval( $match[5] );
				if ( ( count( $match ) > 7 ) && ( $match[7] == '30' ) ) {
					$timeoffset += ( $timeoffset >= 0 ) ? 0.5 : -0.5;
				}
			}

			return [
				'hours' => $nhours,
				'minutes' => $nminutes,
				'seconds' => $nseconds,
				'timeoffset' => $timeoffset,
			];
		}

		return false;
	}

	/**
	 * Parse a military time string, e.g. "134523".
	 *
	 * @return array{hours: int, minutes: int|false, seconds: int|false}|false
	 */
	public static function parseMilTimeString( string $string ): array|false {
		if ( !preg_match( "/^([0-2][0-9])([0-5][0-9])([0-5][0-9])?$/u", $string, $match ) ) {
			return false;
		}

		$nhours = intval( $match[1] );
		$nminutes = $match[2] ? intval( $match[2] ) : false;
		$nseconds = ( ( count( $match ) > 3 ) && $match[3] ) ? intval( $match[3] ) : false;

		if ( ( $nhours < 25 ) && ( ( $nhours < 24 ) || ( $nminutes + $nseconds == 0 ) ) ) {
			return [
				'hours' => $nhours,
				'minutes' => $nminutes,
				'seconds' => $nseconds,
			];
		}

		return false;
	}

}
