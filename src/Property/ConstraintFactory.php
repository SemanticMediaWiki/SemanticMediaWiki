<?php

namespace SMW\Property;

use SMW\Property\Constraint\ConstraintCheckRunner;
use SMW\Property\Constraint\ConstraintRegistry;
use SMW\Property\Constraint\Constraints\NullConstraint;
use SMW\Site;
use SMW\ApplicationFactory;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintFactory {

	/**
	 * @since 3.1
	 *
	 * @return ConstraintRegistry
	 */
	public function newConstraintRegistry() {
		return new ConstraintRegistry( $this );
	}

	/**
	 * @since 3.1
	 *
	 * @return ConstraintCheckRunner
	 */
	public function newConstraintCheckRunner() {
		return new ConstraintCheckRunner( $this->newConstraintRegistry() );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $class
	 *
	 * @return Constraint
	 */
	public function newConstraintByClass( $class ) {
		return $this->newNullConstraint();
	}

	/**
	 * @since 3.1
	 *
	 * @return NullConstraint
	 */
	public function newNullConstraint() {
		return new NullConstraint();
	}

}
