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
	public function hasViolation(): bool {
		return false;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return Constraint::TYPE_INSTANT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function checkConstraint( array $constraint, $value ) {}

}
