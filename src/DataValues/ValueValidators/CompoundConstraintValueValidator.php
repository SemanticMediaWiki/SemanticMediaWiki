<?php

namespace SMW\DataValues\ValueValidators;

use RuntimeException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CompoundConstraintValueValidator implements ConstraintValueValidator {

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @var array
	 */
	private $constraintValueValidators = array();

	/**
	 * @since 2.4
	 *
	 * @param ConstraintValueValidator $constraintValueValidator
	 */
	public function registerConstraintValueValidator( ConstraintValueValidator $constraintValueValidator ) {
		$this->constraintValueValidators[] = $constraintValueValidator;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation() {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {

		$this->hasConstraintViolation = false;

		if ( $this->constraintValueValidators === array() ) {
			throw new RuntimeException( "Missing a registered ConstraintValueValidator" );
		}

		// Any constraint violation by a ConstraintValueValidator registered will
		// force an immediate halt without checking any other possible constraint
		foreach ( $this->constraintValueValidators as $constraintValueValidator ) {
			$constraintValueValidator->validate( $dataValue );

			if ( $constraintValueValidator->hasConstraintViolation() ) {
				$this->hasConstraintViolation = true;
				break;
			}
		}
	}

}
