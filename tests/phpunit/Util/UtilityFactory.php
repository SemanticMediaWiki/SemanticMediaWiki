<?php

namespace SMW\Tests\Util;

use SMW\Tests\Util\Validators\ValidatorFactory;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class UtilityFactory {

	/**
	 * @var UtilityFactory
	 */
	private static $instance = null;

	/**
	 * @since 2.1
	 *
	 * @return UtilityFactory
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 2.1
	 *
	 * @return ValidatorFactory
	 */
	public function newValidatorFactory() {
		return new ValidatorFactory();
	}

	/**
	 * @since 2.1
	 *
	 * @return StringBuilder
	 */
	public function newStringBuilder() {
		return new StringBuilder();
	}

	/**
	 * @since 2.1
	 *
	 * @return MwHooksHandler
	 */
	public function newMwHooksHandler() {
		return new MwHooksHandler();
	}

	/**
	 * @since 2.1
	 *
	 * @return ParserFactory
	 */
	public function newParserFactory() {
		return new ParserFactory();
	}


}
