<?php

namespace SMW\Property\Constraint;

use RuntimwException;
use SMW\Property\ConstraintFactory;
use SMW\Property\Constraint\Constraints\NullConstraint;
use SMW\Property\Constraint\Constraints\CommonConstraint;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintRegistry {

	/**
	 * @var ConstraintFactory
	 */
	private $constraintFactory;

	/**
	 * @var []
	 */
	private $constraints = [];

	/**
	 * @var []
	 */
	private $instances = [];

	/**
	 * @var boolean
	 */
	private $hasViolation = false;

	/**
	 * @since 3.1
	 *
	 * @param ConstraintFactory $constraintFactory
	 */
	public function __construct( ConstraintFactory $constraintFactory ) {
		$this->constraintFactory = $constraintFactory;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param Constraint|string $constraint
	 */
	public function registerConstraint( $key, $constraint ) {

		if ( $this->constraints === [] ) {
			$this->constraints = $this->initConstraints();
		}

		$this->constraints[$key] = $constraint;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return Constraint
	 */
	public function getConstraintByKey( $key ) {

		if ( $this->constraints === [] ) {
			$this->constraints = $this->initConstraints();
		}

		if ( isset( $this->constraints[$key] ) ) {
			return $this->loadInstance( $this->constraints[$key] );
		}

		return $this->loadInstance( $this->constraints['null'] );
	}

	private function initConstraints() {
		return [
			'null' => NullConstraint::class,
			'allowed_namespaces' => CommonConstraint::class
		];
	}

	private function loadInstance( $class ) {

		if ( is_callable( $class ) ) {
			return $class();
		} elseif ( $class instanceof Constraint ) {
			return $class;
		} elseif ( !isset( $this->instances[$class] ) ) {
			$this->instances[$class] = $this->constraintFactory->newConstraintByClass( $class );
		}

		return $this->instances[$class];
	}

}
