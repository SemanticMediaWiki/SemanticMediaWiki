<?php

/**
 * Class for the 'set_recurring_event' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Properties_and_types#Defining_recurring_events
 * 
 * @since 1.5.3
 * 
 * @file SMW_SetRecurringEvent.php
 * @ingroup SMW
 * @ingroup ParserHooks
 * 
 * @author Yaron Koren
 * @author Jeroen De Dauw
 */
class SMWSetRecurringEvent {
	
	/**
	 * Method for handling the set_recurring_event parser function.
	 * 
	 * @since 1.5.3
	 * 
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		// Almost all of the work gets done by
		// getDatesForRecurringEvent().
		$results = self::getDatesForRecurringEvent( $params );
		if ( $results == null ) {
			return null;
		}

		list( $property_name, $all_date_strings, $unused_params ) = $results;

		// Do the actual saving of the data.
		foreach ( $all_date_strings as $date_str ) {
			SMWParseData::addProperty( $property_name, $date_str, false, $parser, true );
		}

		// Starting from MW 1.16, there is a more suited method available: Title::isSpecialPage
		global $wgTitle;
		if ( !is_null( $wgTitle ) && $wgTitle->getNamespace() == NS_SPECIAL ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		}
		else {
			SMWOutputs::commitToParser( $parser );
		}	
	}

	/**
	 * Helper function - creates an object of type SMWTimeValue based
	 * on a "Julian day" integer
	 */
	static protected function jdToTimeValue( $jd ) {
		$timeDataItem = SMWDITime::newFromJD( $jd, SMWDITime::CM_GREGORIAN, SMWDITime::PREC_YMDT );
		$timeValue = new SMWTimeValue( '_dat' );
		$timeValue->setDataItem( $timeDataItem );
		return $timeValue;
	}

	/**
	 * Returns the "Julian day" value from an object of type
	 * SMWTimeValue.
	 */
	static public function getJD( $dateDataValue ) {
		if ( is_null( $dateDataValue ) ) {
			return null;
		}
		$dateDataItem = $dateDataValue->getDataItem();
		// This might have returned an 'SMWDIError' object.
		if ( $dateDataItem instanceof SMWDITime ) {
			return $dateDataItem->getJD();
		}
		return null;
	}
	
	/**
	 * Helper function used in this class, as well as by the
	 * Semantic Internal Objects extension
	 *
	 * @param Parser &$parser The current parser
	 * @return either null, or an array of main property name, set of
	 * all date strings, and the unused params
	 */
	static public function getDatesForRecurringEvent( $params ) {
		// Initialize variables.
		$all_date_strings = array();
		$unused_params = array();
		$property_name = $start_date = $end_date = $unit = $period = $week_num = null;
		$included_dates = array();
		$excluded_dates_jd = array();

		// Set values from the parameters.
		foreach ( $params as $param ) {
			$parts = explode( '=', trim( $param ) );

			if ( count( $parts ) != 2 ) {
				continue;
			}

			list( $name, $value ) = $parts;

			switch( $name ) {
				case 'property':
					$property_name = $value;
					break;
				case 'start':
					$start_date = SMWDataValueFactory::newTypeIDValue( '_dat', $value );
					break;
				case 'end':
					$end_date = SMWDataValueFactory::newTypeIDValue( '_dat', $value );
					break;
				case 'unit':
					$unit = $value;
					break;
				case 'period':
					$period = (int)$value;
					break;
				case 'week number':
					$week_num = (int)$value;
					break;
				case 'include':
					$included_dates = explode( ';', $value );
					break;
				case 'exclude':
					$excluded_dates = explode( ';', $value );

					foreach ( $excluded_dates as $date_str ) {
						$date = SMWDataValueFactory::newTypeIDValue( '_dat', $date_str );
						$excluded_dates_jd[] = self::getJD( $date );
					}
					break;
				default:
					$unused_params[] = $param;
			}
		}

		// We need at least a property and start date - if either one is null, exit here.
		if ( is_null( $property_name ) || is_null( $start_date ) ) {
			return;
		}

		// If the period is null, or outside of normal bounds, set it to 1.
		if ( is_null( $period ) || $period < 1 || $period > 500 ) {
			$period = 1;
		}

		// Handle 'week number', but only if it's of unit 'month'.
		if ( $unit == 'month' && ! is_null( $week_num ) ) {
			$unit = 'dayofweekinmonth';

			if ( $week_num < -4 || $week_num > 5 || $week_num == 0 ) {
				$week_num = null;
			}
		}

		if ( $unit == 'dayofweekinmonth' && is_null( $week_num ) ) {
			$week_num = ceil( $start_date->getDay() / 7 );
		}

		// Get the Julian day value for both the start and end date.
		$end_date_jd = self::getJD( $end_date );

		$cur_date = $start_date;
		$cur_date_jd = self::getJD( $cur_date );
		$i = 0;
		$reached_end_date = false;

		do {
			$i++;
			$exclude_date = ( in_array( $cur_date_jd, $excluded_dates_jd ) );

			if ( !$exclude_date ) {
				$all_date_strings[] = $cur_date->getLongWikiText();
			}

			// Now get the next date.
			// Handling is different depending on whether it's
			// month/year or week/day since the latter is a set
			// number of days while the former isn't.
			if ( $unit === 'year' || $unit == 'month' ) {
				$cur_year = $cur_date->getYear();
				$cur_month = $cur_date->getMonth();
				$cur_day = $cur_date->getDay();
				$cur_time = $cur_date->getTimeString();

				if ( $unit == 'year' ) {
					$cur_year += $period;
					$display_month = $cur_month;
				} else { // $unit === 'month'
					$cur_month += $period;
					$cur_year += (int)( ( $cur_month - 1 ) / 12 );
					$cur_month %= 12;
					$display_month = ( $cur_month == 0 ) ? 12 : $cur_month;
				}

				$date_str = "$cur_year-$display_month-$cur_day $cur_time";
				$cur_date = SMWDataValueFactory::newTypeIDValue( '_dat', $date_str );
			} elseif ( $unit == 'dayofweekinmonth' ) {
				// e.g., "3rd Monday of every month"
				$prev_month = $cur_date->getMonth();
				$prev_year = $cur_date->getYear();

				$new_month = ( $prev_month + $period ) % 12;
				if ( $new_month == 0 ) $new_month = 12;

				$new_year = $prev_year + floor( ( $prev_month + $period - 1 ) / 12 );
				$cur_date_jd += ( 28 * $period ) - 7;

				// We're sometime before the actual date now -
				// keep incrementing by a week, until we get there.
				do {
					$cur_date_jd += 7;
					$cur_date = self::jdToTimeValue( $cur_date_jd );
					$right_month = ( $cur_date->getMonth() == $new_month );

					if ( $week_num < 0 ) {
						$next_week_jd = $cur_date_jd;

						do {
							$next_week_jd += 7;
							$next_week_date = self::jdToTimeValue( $next_week_jd );
							$right_week = ( $next_week_date->getMonth() != $new_month ) || ( $next_week_date->getYear() != $new_year );
						} while ( !$right_week );

						$cur_date_jd = $next_week_jd + ( 7 * $week_num );
						$cur_date = self::jdToTimeValue( $cur_date_jd );
					} else {
						$cur_week_num = ceil( $cur_date->getDay() / 7 );
						$right_week = ( $cur_week_num == $week_num );

						if ( $week_num == 5 && ( $cur_date->getMonth() % 12 == ( $new_month + 1 ) % 12 ) ) {
							$cur_date_jd -= 7;
							$cur_date = self::jdToTimeValue( $cur_date_jd );
							$right_month = $right_week = true;
						}
					}
				} while ( !$right_month || !$right_week);
			} else { // $unit == 'day' or 'week'
				// Assume 'day' if it's none of the above.
				$cur_date_jd += ( $unit === 'week' ) ? 7 * $period : $period;
				$cur_date = self::jdToTimeValue( $cur_date_jd );
			}

			// should we stop?
			if ( is_null( $end_date ) ) {
				global $smwgDefaultNumRecurringEvents;
				$reached_end_date = $i > $smwgDefaultNumRecurringEvents;
			} else {
				global $smwgMaxNumRecurringEvents;
				$reached_end_date = ( $cur_date_jd > $end_date_jd ) || ( $i > $smwgMaxNumRecurringEvents );
			}
		} while ( !$reached_end_date );

		// Handle the 'include' dates as well.
		$all_date_strings = array_merge( $all_date_strings, $included_dates);
		
		return array( $property_name, $all_date_strings, $unused_params );
	}	
	
}
