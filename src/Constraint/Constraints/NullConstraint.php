<?php

namespace SMW\Constraint\Constraints;

use SMW\Constraint\Constraint;

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
	public function getType() {
		return Constraint::TYPE_INSTANT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function checkConstraint( array $constraint, $value ) {}

}
