<?php

namespace SMW\Property;

use SMW\Property\Constraint\ConstraintCheckRunner;
use SMW\Property\Constraint\ConstraintRegistry;
use SMW\Property\Constraint\ConstraintErrorFinder;
use SMW\Property\Constraint\Constraints\NullConstraint;
use SMW\Property\Constraint\ConstraintSchemaCompiler;
use SMW\Property\Constraint\Constraints\CommonConstraint;
use SMW\Site;
use SMW\Store;
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

		switch ( $class ) {
			case CommonConstraint::class:
				$constraint = $this->newCommonConstraint();
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
	 * @return CommonConstraint
	 */
	public function newCommonConstraint() {
		return new CommonConstraint();
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
		$schemaFactory = $applicationFactory->create( 'SchemaFactory');

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
