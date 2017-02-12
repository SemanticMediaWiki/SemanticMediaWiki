<?php

namespace SMW\DataValues\Time;

/**
 * It is assumed that the changeover from the Julian calendar to the Gregorian
 * calendar occurred in October of 1582.
 *
 * For dates on or before 4 October 1582, the Julian calendar is used; for dates
 * on or after 15 October 1582, the Gregorian calendar is used.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
interface CalendarModel {

	/**
	 * Gregorian calendar
	 */
	const CM_GREGORIAN = 1;

	/**
	 * Julian calendar
	 */
	const CM_JULIAN = 2;

}
