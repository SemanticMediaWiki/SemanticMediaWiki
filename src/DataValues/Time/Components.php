<?php

namespace SMW\DataValues\Time;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Components {

	/**
	 * @var array
	 */
	public static $months = [
		'January',
		'February',
		'March',
		'April',
		'May',
		'June',
		'July',
		'August',
		'September',
		'October',
		'November',
		'December'
	];

	/**
	 * @var array
	 */
	public static $monthsShort = [
		'Jan',
		'Feb',
		'Mar',
		'Apr',
		'May',
		'Jun',
		'Jul',
		'Aug',
		'Sep',
		'Oct',
		'Nov',
		'Dec'
	];

	/**
	 * @var []
	 */
	private $components = [];

	/**
	 * @since 3.0
	 *
	 * @param array $components
	 */
	public function __construct( array $components = [] ) {
		$this->components = $components;
	}

	/**
	 * @since 3.0
	 */
	public function get( $key ) {

		if ( isset( $this->components[$key] ) ) {
			return $this->components[$key];
		}

		return false;
	}

}
