<?php

namespace SMW\Property\Constraint\Constraints;

use SMW\Property\Constraint\Constraint;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class NullConstraint implements Constraint {

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasViolation() {
		return false;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function isType( $type ) {
		return $type === Constraint::TYPE_INSTANT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function checkConstraint( array $constraint, $value ) {}

}
