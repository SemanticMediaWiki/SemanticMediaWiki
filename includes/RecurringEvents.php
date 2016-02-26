<?php

namespace SMW;

use SMWDITime;
use SMWTimeValue;

/**
 * This class determines recurring events based on invoked parameters
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Recurring_events
 *
 * @since 1.9
 *
 * @ingroup SMW
 *
 * @author Yaron Koren
 * @author Jeroen De Dauw
 * @author mwjames
 */
class RecurringEvents {

	/**
	 * Represents Settings object
	 */
	private $settings;

	/**
	 * Defines the property used
	 */
	private $property = null;

	/**
	 * Defines the dates
	 */
	private $dates = array();

	/**
	 * Defines remaining / unused parameters
	 */
	private $parameters = array();

	/**
	 * Defines errors
	 */
	private $errors = array();

	/**
	 * @since 1.9
	 *
	 * @param array $parameters
	 * @param Settings $settings
	 */
	public function __construct( array $parameters, Settings $settings ) {
		$this->settings = $settings;
		$this->parse( $parameters );
	}

	/**
	 * Returns property used
	 *
	 * @since  1.9
	 *
	 * @return string
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * Returns dates
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function getDates() {
		return $this->dates;
	}

	/**
	 * Returns unused parameters
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Returns errors
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Set error
	 *
	 * @since  1.9
	 *
	 * @return mixed
	 */
	private function setError( $error ) {
		$this->errors = array_merge( $error, $this->errors );
	}

	/**
	 * Returns the "Julian day" value from an object of type
	 * SMWTimeValue.
	 */
	public function getJulianDay( $dateDataValue ) {
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
	 * Parse parameters and set internal properties
	 *
	 * @since 1.9
	 *
	 * @param array $parameters
	 */
	private function parse( array $parameters ) {
		// Initialize variables.
		$all_date_strings = array();
		$start_date = $end_date = $unit = $period = $week_num = null;
		$included_dates = array();
		$excluded_dates = array();
		$excluded_dates_jd = array();

		// Parse parameters and assign values
		foreach ( $parameters as $name => $values ) {

			foreach ( $values as $value ) {
				switch( $name ) {
					case 'property':
						$this->property = $value;
						break;
					case 'start':
						$start_date = DataValueFactory::getInstance()->newTypeIDValue( '_dat', $value );
						break;
					case 'end':
						$end_date = DataValueFactory::getInstance()->newTypeIDValue( '_dat', $value );
						break;
					case 'limit':
						// Override default limit with query specific limit
						$this->settings->set( 'smwgDefaultNumRecurringEvents', (int)$value );
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
						// This is for compatibility only otherwise we break
						// to much at once. Instead of writing include=...;...
						// it should be include=...;...|+sep=; because the
						// ParameterParser class is conditioned to split those
						// parameter accordingly
						if ( strpos( $value, ';' ) ){
							$included_dates = explode( ';', $value );
						} else {
							$included_dates[] = $value;
						}
						break;
					case 'exclude':
						// Some as above
						if ( strpos( $value, ';' ) ){
							$excluded_dates = explode( ';', $value );
						} else {
							$excluded_dates[] = $value;
						}
						break;
					default:
						$this->parameters[$name][] = $value;
				}
			}
		}

		if ( $start_date === null ) {
			$this->errors[] = Message::get( 'smw-events-start-date-missing' );
			return;
		} elseif ( !( $start_date->getDataItem() instanceof SMWDITime ) ) {
			$this->setError( $start_date->getErrors() );
			return;
		}

		// Check property
		if ( is_null( $this->property ) ) {
			$this->errors[] = Message::get( 'smw-events-property-missing' );
			return;
		}

		// Exclude dates
		foreach ( $excluded_dates as $date_str ) {
			$excluded_dates_jd[] = $this->getJulianDay(
				DataValueFactory::getInstance()->newTypeIDValue( '_dat', $date_str )
			);
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
		$end_date_jd = $this->getJulianDay( $end_date );

		$cur_date = $start_date;
		$cur_date_jd = $this->getJulianDay( $cur_date );
		$i = 0;

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
				$cur_day = $start_date->getDay();
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

				// If the date is February 29, and this isn't
				// a leap year, change it to February 28.
				if ( $cur_month == 2 && $cur_day == 29 ) {
					if ( !date( 'L', strtotime( "$cur_year-1-1" ) ) ) {
						$cur_day = 28;
					}
				}

				$date_str = "$cur_year-$display_month-$cur_day $cur_time";
				$cur_date = DataValueFactory::getInstance()->newTypeIDValue( '_dat', $date_str );
				$all_date_strings = array_merge( $all_date_strings, $included_dates);
				$cur_date_jd = $cur_date->getDataItem()->getJD();
			} elseif ( $unit == 'dayofweekinmonth' ) {
				// e.g., "3rd Monday of every month"
				$prev_month = $cur_date->getMonth();
				$prev_year = $cur_date->getYear();

				$new_month = ( $prev_month + $period ) % 12;
				if ( $new_month == 0 ) {
					$new_month = 12;
				}

				$new_year = $prev_year + floor( ( $prev_month + $period - 1 ) / 12 );
				$cur_date_jd += ( 28 * $period ) - 7;

				// We're sometime before the actual date now -
				// keep incrementing by a week, until we get there.
				do {
					$cur_date_jd += 7;
					$cur_date = $this->getJulianDayTimeValue( $cur_date_jd );
					$right_month = ( $cur_date->getMonth() == $new_month );

					if ( $week_num < 0 ) {
						$next_week_jd = $cur_date_jd;

						do {
							$next_week_jd += 7;
							$next_week_date = $this->getJulianDayTimeValue( $next_week_jd );
							$right_week = ( $next_week_date->getMonth() != $new_month ) || ( $next_week_date->getYear() != $new_year );
						} while ( !$right_week );

						$cur_date_jd = $next_week_jd + ( 7 * $week_num );
						$cur_date = $this->getJulianDayTimeValue( $cur_date_jd );
					} else {
						$cur_week_num = ceil( $cur_date->getDay() / 7 );
						$right_week = ( $cur_week_num == $week_num );

						if ( $week_num == 5 && ( $cur_date->getMonth() % 12 == ( $new_month + 1 ) % 12 ) ) {
							$cur_date_jd -= 7;
							$cur_date = $this->getJulianDayTimeValue( $cur_date_jd );
							$right_month = $right_week = true;
						}
					}
				} while ( !$right_month || !$right_week);
			} else { // $unit == 'day' or 'week'
				// Assume 'day' if it's none of the above.
				$cur_date_jd += ( $unit === 'week' ) ? 7 * $period : $period;
				$cur_date = $this->getJulianDayTimeValue( $cur_date_jd );
			}

			// should we stop?
			if ( is_null( $end_date ) ) {
				$reached_end_date = $i > $this->settings->get( 'smwgDefaultNumRecurringEvents' );
			} else {
				$reached_end_date = ( $cur_date_jd > $end_date_jd ) || ( $i > $this->settings->get( 'smwgMaxNumRecurringEvents' ) );
			}
		} while ( !$reached_end_date );

		// Handle the 'include' dates as well.
		$all_date_strings = array_filter( array_merge( $all_date_strings, $included_dates ) );

		// Set dates
		$this->dates = $all_date_strings;
	}

	/**
	 * Helper function - creates an object of type SMWTimeValue based
	 * on a "Julian day" integer
	 */
	private function getJulianDayTimeValue( $jd ) {
		$timeDataItem = SMWDITime::newFromJD( $jd, SMWDITime::CM_GREGORIAN, SMWDITime::PREC_YMDT );
		$timeValue = new SMWTimeValue( '_dat' );
		$timeValue->setDataItem( $timeDataItem );
		return $timeValue;
	}
}
