<?php

use SMW\DataValues\Time\Components;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\Localizer;
use SMWDITime as DITime;

/**
 * @ingroup SMWDataValues
 */

/**
 * This datavalue captures values of dates and times, in many formats,
 * throughout history and pre-history. The implementation can handle dates
 * across history with full precision for storing, and substantial precision
 * for sorting and querying. The range of supported past dates should encompass
 * the Beginning of Time according to most of today's theories. The range of
 * supported future dates is limited more strictly, but it does also allow
 * year numbers in the order of 10^9.
 *
 * The implementation notices and stores whether parts of a date/time have been
 * omitted (as in "2008" or "May 2007"). For all exporting and sorting
 * purposes, incomplete dates are completed with defaults (usually using the
 * earliest possible time, i.e. interpreting "2008" as "Jan 1 2008 00:00:00").
 * The information on what was unspecified is kept internally for improving
 * behavior e.g. for outputs (defaults are not printed when querying for a
 * value). This largely uses the precision handling of DITime.
 *
 *
 * Date formats
 *
 * Dates can be given in many formats, using numbers, month names, and
 * abbreviated month names. The preferred interpretation of ambiguous dates
 * ("1 2 2008" or even "1 2 3 BC") is controlled by the language file, as is
 * the local naming of months. English month names are always supported.
 *
 * Dates can be given in Gregorian or Julian calendar, set by the token "Jl"
 * or "Gr" in the input. If neither is set, a default is chosen: inputs after
 * October 15, 1582 (the time when the Gregorian calendar was first inaugurated
 * in some parts of the world) are considered Gr, earlier inputs are considered
 * Jl. In addition to Jl and Gr, we support "OS" (Old Style English dates that
 * refer to the use of Julian calendar with a displaced change of year on March
 * 24), JD (direct numerical input in Julian Day notation), and MJD (direct
 * numerical input in Modified Julian Day notation as used in aviation and
 * space flight).
 *
 * The class does not support the input of negative year numbers but uses the
 * markers "BC"/"BCE" and "AD"/"CE" instead. There is no year 0 in Gregorian or
 * Julian calendars, but the class graciously considers this input to mean year
 * 1 BC(E).
 *
 * For prehisoric dates before 9999 BC(E) only year numbers are allowed
 * (nothing else makes much sense). At this time, the years of Julian and
 * Gregorian calendar still overlap significantly, so the transition to a
 * purely solar annotation of prehistoric years is smooth. Technically, the
 * class will consider prehistoric dates as Gregorian but very ancient times
 * may be interpreted as desired (probably with reference to a physical notion
 * of time that is not dependent on revolutions of earth around the sun).
 *
 *
 * Time formats
 *
 * Times can be in formats like "23:12:45" and "12:30" possibly with additional
 * modifiers "am" or "pm". Timezones are supported: the class knows many
 * international timezone monikers (e.g. CET or GMT) and also allows time
 * offsets directly after a time (e.g. "10:30-3:30" or "14:45:23+2"). Such
 * offsets always refer to UTC. Timezones are only used on input and are not
 * stored as part of the value.
 *
 * Time offsets take leap years into account, e.g. the date
 * "Feb 28 2004 23:00+2:00" is equivalent to "29 February 2004 01:00:00", while
 * "Feb 28 1900 23:00+2:00" is equivalent to "1 March 1900 01:00:00".
 *
 * Military time format is supported. This consists of 4 or 6 numeric digits
 * followed by a one-letter timezone code (e.g. 1240Z is equivalent to 12:40
 * UTC).
 *
 *
 * I18N
 *
 * Currently, neither keywords like "BCE", "Jl", or "pm", nor timezone monikers
 * are internationalized. Timezone monikers may not require this, other than
 * possibly for Cyrillic (added when needed). Month names are fully
 * internationalized, but English names and abbreviations will also work in all
 * languages. The class also supports ordinal day-of-month annotations like
 * "st" and "rd", again only for English.
 *
 * I18N includes the preferred order of dates, e.g. to interpret "5 6 2010".
 *
 * @todo Theparsing process can encounter many kinds of well-defined problems
 * but uses only one error message. More detailed reporting should be done.
 * @todo Try to reuse more of MediaWiki's records, e.g. to obtain month names
 * or to format dates. The problem is that MW is based on SIO timestamps that
 * don't extend to very ancient or future dates, and that MW uses PHP functions
 * that are bound to UNIX time.
 *
 * @author Markus Krötzsch
 * @author Fabian Howahl
 * @author Terry A. Hurlbut
 * @ingroup SMWDataValues
 */
class SMWTimeValue extends SMWDataValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '_dat';

	protected $m_dataitem_greg = null;
	protected $m_dataitem_jul = null;

	protected $m_wikivalue; // a suitable wiki input value

	/**
	 * The following are constant (array-valued constants are not supported, hence
	 * the declaration as private static variable):
	 *
	 * @var array
	 */
	protected static $m_formats = [
		SMW_Y   => [ 'y' ],
		SMW_YM  => [ 'y', 'm' ],
		SMW_MY  => [ 'm', 'y' ],
		SMW_YDM => [ 'y', 'd', 'm' ],
		SMW_YMD => [ 'y', 'm', 'd' ],
		SMW_DMY => [ 'd', 'm', 'y' ],
		SMW_MDY => [ 'm', 'd', 'y' ]
	];

	/**
	 *  Moment of switchover to Gregorian calendar.
	 */
	const J1582 = 2299160.5;

	/**
	 * Offset of Julian Days for Modified JD inputs.
	 */
	const MJD_EPOCH = 2400000.5;

	/**
	 * The year before which we do not accept anything but year numbers and
	 * largely discourage calendar models.
	 */
	const PREHISTORY = -10000;

	/**
	 * @see DataValue::parseUserValue
	 */
	protected function parseUserValue( $value ) {

		$value = Localizer::convertDoubleWidth( $value );

		$this->m_wikivalue = $value;
		$this->m_dataitem = null;

		// Store the caption now
		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		$timeValueParser = $this->dataValueServiceFactory->getValueParser(
			$this
		);

		$timeValueParser->clearErrors();

		// Parsing is bound to the content language otherwise any change of a user
		// preferred user language would negate the parsing results
		$timeValueParser->setLanguageCode(
			$this->getOption( self::OPT_CONTENT_LANGUAGE )
		);

		if ( $this->isYear( $value ) ) {
			try {
				$this->m_dataitem = new DITime( $this->getCalendarModel( null, $value, null, null ), $value );
			} catch ( SMWDataItemException $e ) {
				$this->addErrorMsg( [ 'smw-datavalue-time-invalid', $value, $e->getMessage() ] );
			}
		} elseif ( $this->isTimestamp( $value ) ) {
			$this->m_dataitem = DITime::newFromTimestamp( $value );
		} elseif ( ( $components = $timeValueParser->parse( $value ) ) ) {

			$calendarmodel = $components->get( 'calendarmodel' );

			if ( ( $calendarmodel == 'JD' ) || ( $calendarmodel == 'MJD' ) ) {
				$this->setDateFromJD( $components );
			} else {
				$this->setDateFromParsedValues( $components );
			}
		}

		foreach ( $timeValueParser->getErrors() as $err ) {
			$this->addErrorMsg( $err );
		}

		 // Make sure that m_dataitem is set in any case
		if ( $this->m_dataitem === null ) {
			$this->m_dataitem = new DITime( DITime::CM_GREGORIAN, 32202 );
		}
	}

	/**
	 * Validate and interpret the date components as retrieved when parsing
	 * a user input. The method takes care of guessing how a list of values
	 * such as "10 12 13" is to be interpreted using the current language
	 * settings. The result is stored in the call-by-ref parameter
	 * $date that uses keys 'y', 'm', 'd' and contains the respective
	 * numbers as values, or false if not specified. If errors occur, error
	 * messages are added to the objects list of errors, and false is
	 * returned. Otherwise, true is returned.
	 *
	 * @param $datecomponents array of strings that might belong to the specification of a date
	 * @param $date array set to result
	 *
	 * @return boolean stating if successful
	 */
	protected function interpretDateComponents( $datecomponents, &$date ) {

		// The following code segment creates a bit vector to encode
		// which role each digit of the entered date can take (day,
		// year, month). The vector starts with 1 and contains three
		// bits per date component, set ot true whenever this component
		// could be a month, a day, or a year (this is the order).
		// Examples:
		//   100 component could only be a month
		//   010 component could only be a day
		//   001 component could only be a year
		//   011 component could be a day or a year but no month etc.
		// For three components, we thus get a 10 digit bit vector.
		$datevector = 1;
		$propercomponents = [];
		$justfounddash = true; // avoid two dashes in a row, or dashes at the end
		$error = false;
		$numvalue = 0;
		foreach ( $datecomponents as $component ) {
			if ( $component == "-" ) {
				if ( $justfounddash ) {
					$error = true;
					break;
				}
				$justfounddash = true;
			} else {
				$justfounddash = false;
				$datevector = ( $datevector << 3 ) | $this->checkDateComponent( $component, $numvalue );
				$propercomponents[] = $numvalue;
			}
		}

		if ( ( $error ) || ( $justfounddash ) || ( count( $propercomponents ) == 0 ) || ( count( $propercomponents ) > 3 ) ) {

			$msgKey = 'smw-datavalue-time-invalid-date-components';

			if ( $justfounddash ) {
				$msgKey .= '-dash';
			} elseif ( count( $propercomponents ) == 0 ) {
				$msgKey .= '-empty';
			} elseif ( count( $propercomponents ) > 3 ) {
				$msgKey .= '-three';
			} else{
				$msgKey .= '-common';
			}

			$this->addErrorMsg( [ $msgKey, $this->m_wikivalue ] );
			return false;
		}

		// Now use the bitvector to find the preferred interpretation of the date components:
		$dateformats = Localizer::getInstance()->getLang( $this->getOption( self::OPT_CONTENT_LANGUAGE ) )->getDateFormats();
		$date = [ 'y' => false, 'm' => false, 'd' => false ];

		foreach ( $dateformats[count( $propercomponents ) - 1] as $formatvector ) {
			if ( !( ~$datevector & $formatvector ) ) { // check if $formatvector => $datevector ("the input supports the format")
				$i = 0;
				foreach ( self::$m_formats[$formatvector] as $fieldname ) {
					$date[$fieldname] = $propercomponents[$i];
					$i += 1;
				}
				break;
			}
		}

		if ( $date['y'] === false ) { // no band matches the entered date
			$this->addErrorMsg( [ 'smw-datavalue-time-invalid-date-components-sequence', $this->m_wikivalue ] );
			return false;
		}

		return true;
	}

	/**
	 * Initialise data from an anticipated JD value.
	*/
	private function setDateFromJD( $components ) {

		$datecomponents = $components->get( 'datecomponents' );
		$calendarmodel = $components->get( 'calendarmodel' );
		$era = $components->get( 'era' );
		$hours = $components->get( 'hours' );

		if ( ( $era === false ) && ( $hours === false ) && ( $components->get( 'timeoffset' ) == 0 ) ) {
			try {
				$jd = floatval( isset( $datecomponents[1] ) ? $datecomponents[0] . '.' . $datecomponents[1] : $datecomponents[0] );
				if ( $calendarmodel == 'MJD' ) {
					$jd += self::MJD_EPOCH;
				}
				$this->m_dataitem = DITime::newFromJD( $jd, DITime::CM_GREGORIAN, DITime::PREC_YMDT, $components->get( 'timezone' ) );
			} catch ( SMWDataItemException $e ) {
				$this->addErrorMsg( [ 'smw-datavalue-time-invalid-jd', $this->m_wikivalue, $e->getMessage() ] );
			}
		} else {
			$this->addErrorMsg( [ 'smw-datavalue-time-invalid-jd', $this->m_wikivalue, "NO_EXCEPTION" ] );
		}
	}

	/**
	 * Initialise data from the provided intermediate results after
	 * parsing, assuming that a conventional date notation is used.
	 * If errors occur, error messages are added to the objects list of
	 * errors, and false is returned. Otherwise, true is returned.
	 *
	 * @param $datecomponents array of strings that might belong to the specification of a date
	 * @param $calendarmodesl string if model was set in input, otherwise false
	 * @param $era string '+' or '-' if provided, otherwise false
	 * @param $hours integer value between 0 and 24
	 * @param $minutes integer value between 0 and 59
	 * @param $seconds integer value between 0 and 59, or false if not given
	 * @param $timeoffset double value for time offset (e.g. 3.5), or false if not given
	 *
	 * @return boolean stating if successful
	 */
	protected function setDateFromParsedValues( $components ) {

		$datecomponents = $components->get( 'datecomponents' );
		$calendarmodel = $components->get( 'calendarmodel' );
		$era = $components->get( 'era' );
		$hours = $components->get( 'hours' );
		$minutes = $components->get( 'minutes' );
		$seconds = $components->get( 'seconds' );
		$microseconds = $components->get( 'microseconds' );
		$timeoffset = $components->get( 'timeoffset' );
		$timezone = $components->get( 'timezone' );

		$date = false;

		if ( !$this->interpretDateComponents( $datecomponents, $date ) ) {
			return false;
		}

		// Handle BC: the year is negative.
		if ( ( $era == '-' ) && ( $date['y'] > 0 ) ) { // see class documentation on BC, "year 0", and ISO conformance ...
			$date['y'] = -( $date['y'] );
		}

		// Keep information about the era
		if ( ( $era == '+' ) && ( $date['y'] > 0 ) ) {
			$date['y'] = $era . $date['y'];
		}

		// Old Style is a special case of Julian calendar model where the change of the year was 25 March:
		if ( ( $calendarmodel == 'OS' ) &&
		     ( ( $date['m'] < 3 ) || ( ( $date['m'] == 3 ) && ( $date['d'] < 25 ) ) ) ) {
			$date['y']++;
		}

		$calmod = $this->getCalendarModel( $calendarmodel, $date['y'], $date['m'], $date['d'] );

		try {
			$this->m_dataitem = new DITime( $calmod, $date['y'], $date['m'], $date['d'], $hours, $minutes, $seconds . '.' . $microseconds, $timezone );
		} catch ( SMWDataItemException $e ) {
			$this->addErrorMsg( [ 'smw-datavalue-time-invalid', $this->m_wikivalue, $e->getMessage() ] );
			return false;
		}

		// Having more than years or specifying a calendar model does
		// not make sense for prehistoric dates, and our calendar
		// conversion would not be reliable if JD numbers get too huge:
		if ( ( $date['y'] <= self::PREHISTORY ) &&
		     ( ( $this->m_dataitem->getPrecision() > DITime::PREC_Y ) || ( $calendarmodel !== false ) ) ) {
			$this->addErrorMsg( [ 'smw-datavalue-time-invalid-prehistoric', $this->m_wikivalue ] );
			return false;
		}

		if ( $timeoffset != 0 ) {
			$newjd = $this->m_dataitem->getJD() - $timeoffset / 24;
			try {
				$this->m_dataitem = DITime::newFromJD( $newjd, $calmod, $this->m_dataitem->getPrecision(), $timezone );
			} catch ( SMWDataItemException $e ) {
				$this->addErrorMsg( [ 'smw-datavalue-time-invalid-jd', $this->m_wikivalue, $e->getMessage() ] );
				return false;
			}
		}

		return true;
	}

	/**
	 * Check which roles a string component might play in a date, and
	 * set the call-by-ref parameter to the proper numerical
	 * representation. The component string has already been normalized to
	 * be either a plain number, a month name, or a plain number with "d"
	 * pre-pended. The result is a bit vector to indicate the possible
	 * interpretations.
	 *
	 * @param $component string
	 * @param $numvalue integer representing the components value
	 *
	 * @return integer that encodes a three-digit bit vector
	 */
	protected static function checkDateComponent( $component, &$numvalue ) {

		if ( $component === '' ) { // should not happen
			$numvalue = 0;
			return 0;
		} elseif ( is_numeric( $component ) ) {
			$numvalue = intval( $component );
			if ( ( $numvalue >= 1 ) && ( $numvalue <= 12 ) ) {
				return SMW_DAY_MONTH_YEAR; // can be a month, day or year
			} elseif ( ( $numvalue >= 1 ) && ( $numvalue <= 31 ) ) {
				return SMW_DAY_YEAR; // can be day or year
			} else { // number can just be a year
				return SMW_YEAR;
			}
		} elseif ( $component { 0 } == 'd' ) { // already marked as day
			if ( is_numeric( substr( $component, 1 ) ) ) {
				$numvalue = intval( substr( $component, 1 ) );
				return ( ( $numvalue >= 1 ) && ( $numvalue <= 31 ) ) ? SMW_DAY : 0;
			} else {
				return 0;
			}
		}

		$monthnum = array_search( $component, Components::$monthsShort );

		if ( $monthnum !== false ) {
			$numvalue = $monthnum + 1;
			return SMW_MONTH;
		} else {
			return 0;
		}
	}

	/**
	 * Determine the calendar model under which an input should be
	 * interpreted based on the given input data.
	 *
	 * @param $presetmodel mixed string related to a user input calendar model (OS, Jl, Gr) or false
	 * @param $year integer of the given year (adjusted for BC(E), i.e. possibly negative)
	 * @param $month mixed integer of the month or false
	 * @param $day mixed integer of the day or false
	 *
	 * @return integer either DITime::CM_GREGORIAN or DITime::CM_JULIAN
	 */
	protected function getCalendarModel( $presetmodel, $year, $month, $day ) {

		// Old Style is a notational convention of Julian dates only
		if ( $presetmodel == 'OS' ) {
			$presetmodel = 'Jl';
		}

		if ( $presetmodel === 'Gr' || $presetmodel === 'GR' ) {
			return DITime::CM_GREGORIAN;
		} elseif (  $presetmodel === 'Jl' || $presetmodel === 'JL' ) {
			return DITime::CM_JULIAN;
		}

		if ( ( $year > 1582 ) ||
		     ( ( $year == 1582 ) && ( $month > 10 ) ) ||
		     ( ( $year == 1582 ) && ( $month == 10 ) && ( $day > 4 ) ) ) {
			return DITime::CM_GREGORIAN;
		} elseif ( $year > self::PREHISTORY ) {
			return DITime::CM_JULIAN;
		}

		// Proleptic Julian years at some point deviate from the count of complete
		// revolutions of the earth around the sun hence assume that earlier
		// date years are Gregorian (where this effect is very weak). This is
		// mostly for internal use since we will not allow users to specify
		// calendar models at this scale
		return DITime::CM_GREGORIAN;
	}

	/**
	 * @see SMWDataValue::loadDataItem
	 *
	 * {@inheritDoc}
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {

		if ( $dataItem->getDIType() !== SMWDataItem::TYPE_TIME ) {
			return false;
		}

		$this->m_dataitem = $dataItem;
		$this->m_caption = false;
		$this->m_wikivalue = false;

		return true;
	}

	/**
	 * @see SMWDataValue::getShortWikiText
	 *
	 * {@inheritDoc}
	 */
	public function getShortWikiText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::WIKI_SHORT, $linker );
	}

	/**
	 * @see SMWDataValue::getShortHTMLText
	 *
	 * {@inheritDoc}
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::HTML_SHORT, $linker );
	}

	/**
	 * @see SMWDataValue::getLongWikiText
	 *
	 * {@inheritDoc}
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::WIKI_LONG, $linker );
	}

	/**
	 * @see SMWDataValue::getLongHTMLText
	 *
	 * {@inheritDoc}
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::HTML_LONG, $linker );
	}

	/**
	 * @todo The preferred caption may not be suitable as a wiki value (i.e. not parsable).
	 * @see SMWDataValue::getLongHTMLText
	 *
	 * {@inheritDoc}
	 */
	public function getWikiValue() {
		return $this->m_wikivalue ? $this->m_wikivalue : strip_tags( $this->getLongWikiText() );
	}

	/**
	 * @see SMWDataValue::isNumeric
	 *
	 * {@inheritDoc}
	 */
	public function isNumeric() {
		return true;
	}

	/**
	 * Return the year number in the given calendar model, or false if
	 * this number is not available (typically when attempting to get
	 * prehistoric Julian calendar dates). As everywhere in this class,
	 * there is no year 0.
	 *
	 * @param $calendarmodel integer either DITime::CM_GREGORIAN or DITime::CM_JULIAN
	 *
	 * @return mixed typically a number but possibly false
	 */
	public function getYear( $calendarmodel = DITime::CM_GREGORIAN ) {

		$dataItem = $this->getDataItemForCalendarModel(
			$calendarmodel
		);

		if ( $dataItem instanceof DITime ) {
			return $dataItem->getYear();
		}

		return false;
	}

	/**
	 * Return the month number in the given calendar model, or false if
	 * this number is not available (typically when attempting to get
	 * prehistoric Julian calendar dates).
	 *
	 * @param $calendarmodel integer either DITime::CM_GREGORIAN or DITime::CM_JULIAN
	 * @param $default value to return if month is not set at our level of precision
	 *
	 * @return mixed typically a number but possibly anything given as $default
	 */
	public function getMonth( $calendarmodel = DITime::CM_GREGORIAN, $default = 1 ) {

		$dataItem = $this->getDataItemForCalendarModel(
			$calendarmodel
		);

		if ( $dataItem instanceof DITime ) {
			return ( $dataItem->getPrecision() >= DITime::PREC_YM ) ? $dataItem->getMonth() : $default;
		}

		return false;
	}

	/**
	 * Return the day number in the given calendar model, or false if this
	 * number is not available (typically when attempting to get
	 * prehistoric Julian calendar dates).
	 *
	 * @param $calendarmodel integer either DITime::CM_GREGORIAN or DITime::CM_JULIAN
	 * @param $default value to return if day is not set at our level of precision
	 *
	 * @return mixed typically a number but possibly anything given as $default
	 */
	public function getDay( $calendarmodel = DITime::CM_GREGORIAN, $default = 1 ) {

		$dataItem = $this->getDataItemForCalendarModel(
			$calendarmodel
		);

		if ( $dataItem instanceof DITime ) {
			return ( $dataItem->getPrecision() >= DITime::PREC_YMD ) ? $dataItem->getDay() : $default;
		}

		return false;
	}

	/**
	 * @see TimeValueFormatter::getTimeStringFromDataItem
	 *
	 * @return
	 */
	public function getTimeString( $default = '00:00:00' ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->getTimeString( $default );
	}

	/**
	 * @deprecated This method is now called getISO8601Date(). It will vanish before SMW 1.7.
	 */
	public function getXMLSchemaDate( $mindefault = true ) {
		return $this->getISO8601Date( $mindefault );
	}

	/**
	 * @see TimeValueFormatter::getISO8601DateFromDataItem
	 *
	 * @param $mindefault boolean determining whether values below the
	 * precision of our input should be completed with minimal or maximal
	 * conceivable values
	 *
	 * @return string
	 */
	public function getISO8601Date( $mindefault = true ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->getISO8601Date( $mindefault );
	}

	/**
	 * @see TimeValueFormatter::getMediaWikiDateFromDataItem
	 *
	 * @return string
	 */
	public function getMediaWikiDate() {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->getMediaWikiDate();
	}

	/**
	 * Get the current data in the specified calendar model. Conversion is
	 * not done for prehistoric dates (where it might lead to precision
	 * errors and produce results that are not meaningful). In this case,
	 * null might be returned if no data in the specified format is
	 * available.
	 *
	 * @param $calendarmodel integer one of DITime::CM_GREGORIAN or DITime::CM_JULIAN
	 *
	 * @return DITime
	 */
	public function getDataItemForCalendarModel( $calendarmodel ) {
		if ( $this->m_dataitem->getYear() <= self::PREHISTORY ) {
			return ( $this->m_dataitem->getCalendarModel() == $calendarmodel ) ? $this->m_dataitem : null;
		} elseif ( $calendarmodel == DITime::CM_GREGORIAN ) {
			if ( is_null( $this->m_dataitem_greg ) ) {
				$this->m_dataitem_greg = $this->m_dataitem->getForCalendarModel( DITime::CM_GREGORIAN );
			}
			return $this->m_dataitem_greg;
		} else {
			if ( is_null( $this->m_dataitem_jul ) ) {
				$this->m_dataitem_jul = $this->m_dataitem->getForCalendarModel( DITime::CM_JULIAN );
			}
			return $this->m_dataitem_jul;
		}
	}

	private function isYear( $value ) {
		return strpos( $value, ' ' ) === false && is_numeric( strval( $value ) ) && ( strval( $value ) < 0 || strlen( $value ) < 6 );
	}

	private function isTimestamp( $value ) {
		// 1200-11-02T12:03:25 or 20120320055913
		// avoid things like 2458119.500000 (JD)
		return ( ( strlen( $value ) > 4 && substr( $value, 10, 1 ) === 'T' ) || ( strlen( $value ) == 14 && strpos( $value, '.' ) === false ) ) && wfTimestamp( TS_MW, $value ) !== false;
	}

}
