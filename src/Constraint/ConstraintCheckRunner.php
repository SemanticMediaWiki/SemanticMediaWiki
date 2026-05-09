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

	private array $constraintChecks = [];

	private array $constraints = [];

	private array $constraintSchemas = [];

	private bool $hasViolation = false;

	private bool $hasDeferrableConstraint = false;

	/**
	 * @since 3.1
	 */
	public function __construct( private readonly ConstraintRegistry $constraintRegistry ) {
	}

	/**
	 * @since 3.1
	 */
	public function hasViolation(): bool {
		return $this->hasViolation;
	}

	/**
	 * @since 3.1
	 */
	public function hasDeferrableConstraint(): bool {
		return $this->hasDeferrableConstraint;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param Schema|SchemaList $schema
	 */
	public function load( $key, $schema ): void {
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
	 */
	public function getConstraints(): array {
		return $this->constraints;
	}

	/**
	 * @since 3.1
	 */
	public function check( mixed $dataValue ): void {
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
			$this->hasDeferrableConstraint = true;
			return $this->hasDeferrableConstraint;
		}

		$constraint->checkConstraint( [ $key => $value ], $dataValue );
		$this->hasViolation = $constraint->hasViolation();
	}

}
