<?php

namespace SMW\Constraint;

use RuntimeException;
use SMW\Schema\Schema;
use SMW\Schema\SchemaList;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintCheckRunner {

	/**
	 * @var ConstraintRegistry
	 */
	private $constraintRegistry;

	/**
	 * @var
	 */
	private $constraintChecks = [];

	/**
	 * @var
	 */
	private $constraints = [];

	/**
	 * @var
	 */
	private $constraintSchemas = [];

	/**
	 * @var bool
	 */
	private $hasViolation = false;

	/**
	 * @var bool
	 */
	private $hasDeferrableConstraint = false;

	/**
	 * @since 3.1
	 *
	 * @param ConstraintRegistry $constraintRegistry
	 */
	public function __construct( ConstraintRegistry $constraintRegistry ) {
		$this->constraintRegistry = $constraintRegistry;
	}

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function hasViolation() {
		return $this->hasViolation;
	}

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function hasDeferrableConstraint() {
		return $this->hasDeferrableConstraint;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param Schema|SchemaList $schema
	 */
	public function load( $key, $schema ) {
		$this->hasViolation = false;
		$this->constraints = [];

		if ( !isset( $this->constraintSchemas[$key] ) && $schema instanceof Schema ) {
			$this->constraintSchemas[$key] = $schema->get( 'constraints' );
		}

		if ( !isset( $this->constraintSchemas[$key] ) && $schema instanceof SchemaList ) {
			$schema = $schema->merge( $schema );

			if ( isset( $schema['constraints'] ) ) {
				$this->constraintSchemas[$key] = $schema['constraints'];
			}
		}

		if ( isset( $this->constraintSchemas[$key] ) ) {
			$this->constraints = $this->constraintSchemas[$key];
		}
	}

	/**
	 * @since 3.1
	 *
	 * @return
	 */
	public function getConstraints() {
		return $this->constraints;
	}

	/**
	 * @since 3.1
	 *
	 * @param mixed $dataValue
	 */
	public function check( $dataValue ) {
		$this->hasDeferrableConstraint = false;
		$this->hasViolation = false;

		foreach ( $this->constraints as $key => $value ) {

			// Bailout in case there is already a violation
			if ( $this->hasViolation ) {
				break;
			}

			if ( $key === 'custom_constraint' ) {
				foreach ( $value as $k => $v ) {
					$this->checkConstraint( $k, $v, $dataValue );
				}
			} else {
				$this->checkConstraint( $key, $value, $dataValue );
			}
		}
	}

	private function checkConstraint( $key, $value, $dataValue ) {
		$constraint = $this->constraintRegistry->getConstraintByKey(
			$key
		);

		if ( !$constraint instanceof Constraint ) {
			throw new RuntimeException( "The `$key` key has a non Constraint instance assigned!" );
		}

		if ( $constraint->getType() === Constraint::TYPE_DEFERRED ) {
			return $this->hasDeferrableConstraint = true;
		}

		$constraint->checkConstraint( [ $key => $value ], $dataValue );
		$this->hasViolation = $constraint->hasViolation();
	}

}
