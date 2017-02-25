<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;
use SMW\DataValues\ValueParsers\AllowsPatternContentParser;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ValueParserFactory {

	/**
	 * @var ValueParserFactory
	 */
	private static $instance = null;

	/**
	 * @since 2.2
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 2.2
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.4
	 *
	 * @return MonolingualTextValueParser
	 */
	public function newMonolingualTextValueParser() {
		return new MonolingualTextValueParser();
	}

}
