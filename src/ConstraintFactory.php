<?php

namespace SMW;

use RuntimeException;
use SMW\Exception\ClassNotFoundException;
use SMW\Constraint\ConstraintCheckRunner;
use SMW\Constraint\ConstraintRegistry;
use SMW\Constraint\ConstraintErrorFinder;
use SMW\Constraint\Constraint;
use SMW\Constraint\Constraints\NullConstraint;
use SMW\Constraint\ConstraintSchemaCompiler;
use SMW\Constraint\Constraints\NamespaceConstraint;
use SMW\Constraint\Constraints\UniqueValueConstraint;
use SMW\Constraint\Constraints\NonNegativeIntegerConstraint;
use SMW\Constraint\Constraints\MustExistsConstraint;
use SMW\Constraint\Constraints\SingleValueConstraint;
use SMW\Constraint\Constraints\MandatoryPropertiesConstraint;
use SMW\Constraint\Constraints\ShapeConstraint;
use SMW\Options;

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
	 * @return Options
	 */
	public function newConstraintOptions() {

		$applicationFactory = ApplicationFactory::getInstance();
		$schemaTypes = $applicationFactory->getSettings()->get( 'smwgSchemaTypes' );

		$options = [
			Constraint::CLASS_CONSTRAINT_SCHEMA => $schemaTypes[Constraint::CLASS_CONSTRAINT_SCHEMA],
			Constraint::PROPERTY_CONSTRAINT_SCHEMA => $schemaTypes[Constraint::PROPERTY_CONSTRAINT_SCHEMA]
		];

		return new Options( $options );
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
	 * @throws RuntimeException
	 */
	public function newConstraintByClass( $class ) {

		if ( !class_exists( $class ) ) {
			throw new ClassNotFoundException( $class );
		}

		switch ( $class ) {
			case NamespaceConstraint::class:
				$constraint = $this->newNamespaceConstraint();
				break;
			case MandatoryPropertiesConstraint::class:
				$constraint = $this->newMandatoryPropertiesConstraint();
				break;
			case ShapeConstraint::class:
				$constraint = $this->newShapeConstraint();
				break;
			case UniqueValueConstraint::class:
				$constraint = $this->newUniqueValueConstraint();
				break;
			case NonNegativeIntegerConstraint::class:
				$constraint = $this->newNonNegativeIntegerConstraint();
				break;
			case MustExistsConstraint::class:
				$constraint = $this->newMustExistsConstraint();
				break;
			case SingleValueConstraint::class:
				$constraint = $this->newSingleValueConstraint();
				break;
			case NullConstraint::class:
				$constraint = $this->newNullConstraint();
				break;
			default:
				$constraint = new $class();
				break;
		}

		if ( !$constraint instanceof Constraint ) {
			throw new RuntimeException( "Expected a `Constraint` instance!" );
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
	 * @return MandatoryPropertiesConstraint
	 */
	public function newMandatoryPropertiesConstraint() {
		return new MandatoryPropertiesConstraint();
	}

	/**
	 * @since 3.1
	 *
	 * @return ShapeConstraint
	 */
	public function newShapeConstraint() {
		return new ShapeConstraint();
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
	 * @return MustExistsConstraint
	 */
	public function newMustExistsConstraint() {
		return new MustExistsConstraint();
	}

	/**
	 * @since 3.1
	 *
	 * @return SingleValueConstraint
	 */
	public function newSingleValueConstraint() {
		return new SingleValueConstraint();
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

}
