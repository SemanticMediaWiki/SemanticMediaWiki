<?php

namespace SMW;

use SMW\Constraint\ConstraintCheckRunner;
use SMW\Constraint\ConstraintRegistry;
use SMW\Constraint\ConstraintErrorFinder;
use SMW\Constraint\Constraints\NullConstraint;
use SMW\Constraint\ConstraintSchemaCompiler;
use SMW\Constraint\Constraints\NamespaceConstraint;
use SMW\Constraint\Constraints\UniqueValueConstraint;
use SMW\Constraint\Constraints\NonNegativeIntegerConstraint;

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

		switch ( $class ) {
			case NamespaceConstraint::class:
				$constraint = $this->newNamespaceConstraint();
				break;
			case UniqueValueConstraint::class:
				$constraint = $this->newUniqueValueConstraint();
				break;
			case NonNegativeIntegerConstraint::class:
				$constraint = $this->newNonNegativeIntegerConstraint();
				break;
			default:
				$constraint = $this->newNullConstraint();
				break;
		}

		return $constraint;
	}

	/**
	 * @since 3.1
	 *
	 * @return NamespaceConstraint
	 */
	public function newNamespaceConstraint() {
		return new NamespaceConstraint();
	}

	/**
	 * @since 3.1
	 *
	 * @return UniqueValueConstraint
	 */
	public function newUniqueValueConstraint() {

		$applicationFactory = ApplicationFactory::getInstance();

		$uniqueValueConstraint = new UniqueValueConstraint(
			$applicationFactory->getStore(),
			$applicationFactory->getPropertySpecificationLookup()
		);

		return $uniqueValueConstraint;
	}

	/**
	 * @since 3.1
	 *
	 * @return NonNegativeIntegerConstraint
	 */
	public function newNonNegativeIntegerConstraint() {
		return new NonNegativeIntegerConstraint();
	}

	/**
	 * @since 3.1
	 *
	 * @return NullConstraint
	 */
	public function newNullConstraint() {
		return new NullConstraint();
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 *
	 * @return ConstraintSchemaCompiler
	 */
	public function newConstraintSchemaCompiler( Store $store ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$schemaFactory = $applicationFactory->create( 'SchemaFactory' );

		$constraintSchemaCompiler = new ConstraintSchemaCompiler(
			$schemaFactory->newSchemaFinder( $store ),
			$applicationFactory->getPropertySpecificationLookup()
		);

		return $constraintSchemaCompiler;
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 *
	 * @return ConstraintErrorFinder
	 */
	public function newConstraintErrorFinder( Store $store ) {
		return new ConstraintErrorFinder( $store );
	}

}
