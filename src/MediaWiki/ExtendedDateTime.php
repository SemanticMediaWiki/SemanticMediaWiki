<?php

namespace SMW\MediaWiki;

use DateTime;

/**
 * This class extends DateTime to allow "hasLocalTimeCorrection"
 * property to be added. PHP 8 doesn't allow dynamic properties unless
 * you set an option in the class to allow it. Since DateTime is a core class,
 * it can't be changed. Instead we extend the class and allow it.
 *
 * @license GPL-2.0-or-later
 * @since 6.0
 */
class ExtendedDateTime extends DateTime {

	/**
	 * @var bool
	 */
	public $hasLocalTimeCorrection = false;
}
