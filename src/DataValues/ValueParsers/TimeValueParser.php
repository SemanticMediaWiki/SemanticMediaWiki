<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\Time\Components;
use SMW\DataValues\Time\TimeStringParser;
use SMW\DataValues\Time\Timezone;
use SMW\Localizer\Localizer;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author Markus Krötzsch
 * @author Fabian Howahl
 * @author Terry A. Hurlbut
 * @author mwjames
 */
class TimeValueParser implements ValueParser {

	private const TYPE_SPACE = 'space';
	private const TYPE_DASH = 'dash';
	private const TYPE_NUMBER = 'number';
	private const TYPE_ERA = 'era';
	private const TYPE_CALENDAR_MODEL = 'calendar_model';
	private const TYPE_AMPM = 'ampm';
	private const TYPE_TIME = 'time';
	private const TYPE_MILITARY_TZ = 'military_tz';
	private const TYPE_TIMEZONE = 'timezone';
	private const TYPE_MONTH = 'month';
	private const TYPE_ORDINAL_SUFFIX = 'ordinal_suffix';
	private const TYPE_OTHER = 'other';

	private const ERA_POSITIVE = [ 'AD', 'CE' ];
	private const ERA_NEGATIVE = [ 'BC', 'BCE' ];
	private const CALENDAR_MODELS = [ 'Gr', 'GR', 'He', 'Jl', 'JL', 'MJD', 'JD', 'OS' ];
	private const ORDINAL_SUFFIXES = [ 'st', 'nd', 'rd', 'th' ];

	private array $errors = [];
	private string $userValue = '';
	private string $languageCode = 'en';

	/**
	 * @since 3.0
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 */
	public function setLanguageCode( string $languageCode ): void {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 3.0
	 */
	public function clearErrors(): void {
		$this->errors = [];
	}

	/**
	 * @since 3.0
	 */
	public function parse( $userValue ): Components|false {
		$this->errors = [];
		$this->userValue = $userValue;

		return $this->parseDateString( $userValue );
	}

	/**
	 * Pre-process the input string, tokenize, classify, and assemble into
	 * a Components result.
	 */
	private function parseDateString( string $string ): Components|false {
		$timezoneoffset = false;
		$timezone = false;

		// Fetch possible "America/Argentina/Mendoza"
		$timzoneIdentifier = substr( $string, strrpos( $string, ' ' ) + 1 );

		if ( Timezone::isValid( $timzoneIdentifier ) ) {
			$string = str_replace( $timzoneIdentifier, '', $string );
			$timezoneoffset = Timezone::getOffsetByAbbreviation( $timzoneIdentifier ) / 3600;
			$timezone = Timezone::getIdByAbbreviation( $timzoneIdentifier );
		}

		// Handle RFC 2822 offset notation, e.g. `2002-11-01T00:00:00.000-0800`
		// or `2018-10-11 18:23:59 +0200`
		$offsetMarker = substr( $string, -5, 1 );

		if ( ( $offsetMarker === '+' || $offsetMarker === '-' ) && substr_count( $string, ':' ) == 2 ) {
			$string = date( 'c', strtotime( $string ) ); // ISO 8601 date
		}

		// Normalize date separation characters
		$parsevalue = str_replace(
			[ '/', '.', '&nbsp;', ',', '年', '月', '日', '時', '分' ],
			[ '-', ' ', ' ', ' ', ' ', ' ', ' ', ':', ' ' ],
			$string
		);

		$regex = "/([T]?[0-2]?[0-9]:[\:0-9]+[+\-]?[0-2]?[0-9\:]+|[\p{L}]+|[0-9]+|[ ])/u";
		$rawTokens = preg_split( $regex, $parsevalue, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$classifiedTokens = $this->classifyTokens( $rawTokens );

		return $this->assembleComponents( $classifiedTokens, $this->userValue, $timezoneoffset, $timezone );
	}

	/**
	 * Classify each token independently.
	 *
	 * @param string[] $tokens
	 *
	 * @return array<array{type: string, value: string}>
	 */
	private function classifyTokens( array $tokens ): array {
		$classified = [];

		foreach ( $tokens as $token ) {
			$classified[] = $this->classifyToken( $token );
		}

		return $classified;
	}

	/**
	 * Classify a single token. Priority order matches the original elseif chain.
	 *
	 * @return array{type: string, value: string}
	 */
	private function classifyToken( string $token ): array {
		if ( $token === ' ' ) {
			return [ 'type' => self::TYPE_SPACE, 'value' => $token ];
		}

		if ( $token === '-' ) {
			return [ 'type' => self::TYPE_DASH, 'value' => $token ];
		}

		if ( is_numeric( $token ) ) {
			return [ 'type' => self::TYPE_NUMBER, 'value' => $token ];
		}

		if ( in_array( $token, self::ERA_POSITIVE, true ) ) {
			return [ 'type' => self::TYPE_ERA, 'value' => '+' ];
		}

		if ( in_array( $token, self::ERA_NEGATIVE, true ) ) {
			return [ 'type' => self::TYPE_ERA, 'value' => '-' ];
		}

		if ( in_array( $token, self::CALENDAR_MODELS, true ) ) {
			return [ 'type' => self::TYPE_CALENDAR_MODEL, 'value' => $token ];
		}

		$lower = strtolower( $token );
		if ( $lower === 'am' || $lower === 'pm' ) {
			return [ 'type' => self::TYPE_AMPM, 'value' => $lower ];
		}

		if ( TimeStringParser::parseTimeString( $token ) !== false ) {
			return [ 'type' => self::TYPE_TIME, 'value' => $token ];
		}

		if ( Timezone::isMilitary( $token ) ) {
			return [ 'type' => self::TYPE_MILITARY_TZ, 'value' => $token ];
		}

		if ( Timezone::isValid( $token ) ) {
			return [ 'type' => self::TYPE_TIMEZONE, 'value' => $token ];
		}

		$monthname = '';
		if ( $this->parseMonthString( $token, $monthname ) ) {
			return [ 'type' => self::TYPE_MONTH, 'value' => $monthname ];
		}

		if ( in_array( $token, self::ORDINAL_SUFFIXES, true ) ) {
			return [ 'type' => self::TYPE_ORDINAL_SUFFIX, 'value' => $token ];
		}

		return [ 'type' => self::TYPE_OTHER, 'value' => $token ];
	}

	/**
	 * Resolve context-dependent token interpretations and build a Components
	 * result.
	 *
	 * @param array<array{type: string, value: string}> $tokens
	 * @param string $originalValue
	 * @param int|float|false $preTimezoneoffset timezone offset from pre-processing
	 * @param string|false $preTimezone timezone ID from pre-processing
	 */
	private function assembleComponents(
		array $tokens,
		string $originalValue,
		int|float|false $preTimezoneoffset,
		string|false $preTimezone,
	): Components|false {
		[ $era, $calendarmodel, $ampm ] = $this->extractSingletons( $tokens );

		[ $hours, $minutes, $seconds, $timeoffset, $timezoneoffset, $timezone, $timeFoundAtIndex ]
			= $this->extractTime( $tokens, $preTimezoneoffset, $preTimezone );

		if ( $timeFoundAtIndex === false ) {
			[ $hours, $minutes, $seconds, $timezoneoffset, $timezone, $timeFoundAtIndex, $tokens ]
				= $this->resolveMilitaryTimezone( $tokens, $timezoneoffset, $timezone );
		}

		if ( $timeFoundAtIndex !== false ) {
			[ $timezoneoffset, $timezone ]
				= $this->resolveTimezone( $tokens, $timeFoundAtIndex, $timezoneoffset, $timezone );
		}

		[ $datecomponents, $microseconds ] = $this->buildDateComponents( $tokens );

		// Validation
		if ( $timezoneoffset !== false && $timeoffset !== false ) {
			$this->errors[] = [ 'smw-datavalue-time-invalid-offset-zone-usage', $this->userValue ];
			return false;
		}

		$timeoffset += $timezoneoffset;

		// Apply am/pm
		if ( $ampm !== false && ( $hours > 12 || $hours == 0 ) ) {
			$this->errors[] = [ 'smw-datavalue-time-invalid-ampm', $this->userValue, $hours ];
			return false;
		} elseif ( $ampm === 'am' && $hours == 12 ) {
			$hours = 0;
		} elseif ( $ampm === 'pm' && $hours < 12 ) {
			$hours += 12;
		}

		// Default to JD input if a single number was given as the date
		if ( ( $calendarmodel === false ) && ( $era === false )
			&& ( count( $datecomponents ) == 1 || count( $datecomponents ) == 2 )
			&& ( intval( end( $datecomponents ) ) >= 100000 ) ) {
			$calendarmodel = 'JD';
		}

		return new Components( [
			'value' => $originalValue,
			'datecomponents' => $datecomponents,
			'calendarmodel' => $calendarmodel,
			'era' => $era,
			'hours' => $hours,
			'minutes' => $minutes,
			'seconds' => $seconds,
			'microseconds' => $microseconds,
			'timeoffset' => $timeoffset,
			'timezone' => $timezone,
		] );
	}

	/**
	 * Extract first-occurrence singleton tokens: era, calendar model, am/pm.
	 *
	 * @param array<array{type: string, value: string}> $tokens
	 *
	 * @return array{string|false, string|false, string|false}
	 */
	private function extractSingletons( array $tokens ): array {
		$era = false;
		$calendarmodel = false;
		$ampm = false;

		foreach ( $tokens as $token ) {
			if ( $era === false && $token['type'] === self::TYPE_ERA ) {
				$era = $token['value'];
			} elseif ( $calendarmodel === false && $token['type'] === self::TYPE_CALENDAR_MODEL ) {
				$calendarmodel = $token['value'];
			} elseif ( $ampm === false && $token['type'] === self::TYPE_AMPM ) {
				$ampm = $token['value'];
			}
		}

		return [ $era, $calendarmodel, $ampm ];
	}

	/**
	 * Find the first time token and parse it.
	 *
	 * @param array<array{type: string, value: string}> $tokens
	 * @param int|float|false $preTimezoneoffset
	 * @param string|false $preTimezone
	 *
	 * @return array{int|false, int|false, int|false, int|float|false, int|float|false, string|false, int|false}
	 */
	private function extractTime(
		array $tokens,
		int|float|false $preTimezoneoffset,
		string|false $preTimezone,
	): array {
		$hours = false;
		$minutes = false;
		$seconds = false;
		$timeoffset = false;
		$timeFoundAtIndex = false;

		foreach ( $tokens as $i => $token ) {
			if ( $token['type'] === self::TYPE_TIME ) {
				$timeResult = TimeStringParser::parseTimeString( $token['value'] );
				if ( $timeResult !== false ) {
					$hours = $timeResult['hours'];
					$minutes = $timeResult['minutes'];
					$seconds = $timeResult['seconds'];
					$timeoffset = $timeResult['timeoffset'];
					$timeFoundAtIndex = $i;
					break;
				}
			}
		}

		return [ $hours, $minutes, $seconds, $timeoffset, $preTimezoneoffset, $preTimezone, $timeFoundAtIndex ];
	}

	/**
	 * Resolve military timezone notation: a number followed by a military
	 * timezone letter. The number is reinterpreted as military time.
	 *
	 * @param array<array{type: string, value: string}> $tokens
	 * @param int|float|false $timezoneoffset
	 * @param string|false $timezone
	 *
	 * @return array{int|false, int|false, int|false, int|float|false, string|false, int|false, array}
	 */
	private function resolveMilitaryTimezone(
		array $tokens,
		int|float|false $timezoneoffset,
		string|false $timezone,
	): array {
		$hours = false;
		$minutes = false;
		$seconds = false;
		$timeFoundAtIndex = false;
		$prevNumberIndex = false;
		$prevNumberValue = '';

		foreach ( $tokens as $i => $token ) {
			if ( $token['type'] === self::TYPE_NUMBER ) {
				$prevNumberIndex = $i;
				$prevNumberValue = $token['value'];
			} elseif ( $token['type'] === self::TYPE_MILITARY_TZ && $prevNumberIndex !== false ) {
				$milResult = TimeStringParser::parseMilTimeString( $prevNumberValue );
				if ( $milResult !== false ) {
					$hours = $milResult['hours'];
					$minutes = $milResult['minutes'];
					$seconds = $milResult['seconds'];
					$timezoneoffset = Timezone::getOffsetByAbbreviation( $token['value'] ) / 3600;
					$timezone = Timezone::getIdByAbbreviation( $token['value'] );
					$timeFoundAtIndex = $i;
					// Mark the number token as consumed
					$tokens[$prevNumberIndex]['type'] = self::TYPE_SPACE;
					break;
				}
			} else {
				// Any non-number token (including spaces) resets the previous
				// number tracking, matching the original behavior where spaces
				// set $prevmatchwasnumber to false.
				$prevNumberIndex = false;
			}
		}

		return [ $hours, $minutes, $seconds, $timezoneoffset, $timezone, $timeFoundAtIndex, $tokens ];
	}

	/**
	 * Resolve timezone abbreviation appearing after the time position.
	 *
	 * @param array<array{type: string, value: string}> $tokens
	 * @param int $timeFoundAtIndex
	 * @param int|float|false $timezoneoffset
	 * @param string|false $timezone
	 *
	 * @return array{int|float|false, string|false}
	 */
	private function resolveTimezone(
		array $tokens,
		int $timeFoundAtIndex,
		int|float|false $timezoneoffset,
		string|false $timezone,
	): array {
		if ( $timezoneoffset === false ) {
			foreach ( $tokens as $i => $token ) {
				if ( $i > $timeFoundAtIndex && $token['type'] === self::TYPE_TIMEZONE ) {
					$timezoneoffset = Timezone::getOffsetByAbbreviation( $token['value'] ) / 3600;
					$timezone = Timezone::getIdByAbbreviation( $token['value'] );
					break;
				}
			}
		}

		return [ $timezoneoffset, $timezone ];
	}

	/**
	 * Build date components from a contiguous block of numbers, dashes,
	 * and month names. Collects any unrecognized tokens as unclear parts.
	 *
	 * @param array<array{type: string, value: string}> $tokens
	 *
	 * @return array{string[], string|false}
	 */
	private function buildDateComponents( array $tokens ): array {
		$datecomponents = [];
		$microseconds = false;
		$inDateBlock = false;
		$prevWasNumber = false;

		foreach ( $tokens as $token ) {
			$type = $token['type'];

			if ( $type === self::TYPE_SPACE ) {
				continue;
			}

			if ( $type === self::TYPE_NUMBER && ( $inDateBlock || $datecomponents === [] ) ) {
				$datecomponents[] = $token['value'];
				$inDateBlock = true;
				$prevWasNumber = true;
				continue;
			}

			// A number outside the date block (e.g. "100" from "12:00:00.100"
			// after dot→space normalization) is microseconds.
			if ( $type === self::TYPE_NUMBER ) {
				$microseconds = $token['value'];
				$inDateBlock = false;
				$prevWasNumber = false;
				continue;
			}

			if ( $type === self::TYPE_DASH ) {
				$datecomponents[] = $token['value'];
				$inDateBlock = true;
				$prevWasNumber = false;
				continue;
			}

			if ( $type === self::TYPE_MONTH && ( $inDateBlock || $datecomponents === [] ) ) {
				$datecomponents[] = $token['value'];
				$inDateBlock = true;
				$prevWasNumber = false;
				continue;
			}

			if ( $type === self::TYPE_ORDINAL_SUFFIX && $prevWasNumber && $inDateBlock ) {
				$last = array_pop( $datecomponents );
				$datecomponents[] = 'd' . strval( $last );
				$prevWasNumber = false;
				continue;
			}

			if ( $type === self::TYPE_OTHER ) {
				$microseconds = $token['value'];
			}

			// All non-date tokens end the date block (except spaces handled above)
			$inDateBlock = false;
			$prevWasNumber = false;
		}

		return [ $datecomponents, $microseconds ];
	}

	/**
	 * Check if the string refers to a month name or abbreviation.
	 */
	private function parseMonthString( string $string, string &$monthname ): bool {
		$monthnum = Localizer::getInstance()->getLang(
			$this->languageCode
		)->findMonthNumberByLabel( $string );

		if ( $monthnum !== false ) {
			$monthnum -= 1;
		} else {
			$monthnum = array_search( $string, Components::$months );
		}

		if ( $monthnum !== false ) {
			$monthname = Components::$monthsShort[$monthnum];
			return true;
		} elseif ( array_search( $string, Components::$monthsShort ) !== false ) {
			$monthname = $string;
			return true;
		}

		return false;
	}

}
