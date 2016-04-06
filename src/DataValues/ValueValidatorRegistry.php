<?php

namespace SMW\DataValues;

use SMW\DataValues\ValueValidators\CompoundConstraintValueValidator;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ValueValidatorRegistry {

	/**
	 * @var ValueValidatorRegistry
	 */
	private static $instance = null;

	/**
	 * @var CompoundConstraintValueValidator
	 */
	private $compoundConstraintValueValidator = null;

	/**
	 * @since 2.4
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
	 * @since 2.4
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.4
	 *
	 * @return ConstraintValueValidator
	 */
	public function getConstraintValueValidator() {

		if ( $this->compoundConstraintValueValidator === null ) {
			$this->compoundConstraintValueValidator = new CompoundConstraintValueValidator();
		}

		return $this->compoundConstraintValueValidator;
	}

}
