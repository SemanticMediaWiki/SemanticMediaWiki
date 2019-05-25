<?php

namespace SMW\Constraint;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
interface Constraint {

	/**
	 * Schema types
	 */
	const CLASS_CONSTRAINT_SCHEMA = 'CLASS_CONSTRAINT_SCHEMA';
	const PROPERTY_CONSTRAINT_SCHEMA = 'PROPERTY_CONSTRAINT_SCHEMA';

	/**
	 * The constraint check happens instantaneous on a GET request (aka. online)
	 * and should be used for "light" checks that doesn't involve the `QueryEngine`
	 * or a rule resolver given the potential computational requirements that
	 * are required to run checks on each individual value.
	 */
	const TYPE_INSTANT = 'type/instant';

	/**
	 * The constraint check happens after a GET request using the job queue.
	 */
	const TYPE_DEFERRED = 'type/deferred';

	/**
	 * Returns true when a violation during the check occurred.
	 *
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function hasViolation();

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Checks a constraint against a single value. Any error that occurred during
	 * the processing should be attached to an individual value using the
	 * `ConstraintError` class.
	 *
	 * @since 3.1
	 *
	 * @param array $constraint
	 * @param mixed $value
	 */
	public function checkConstraint( array $constraint, $value );

}
