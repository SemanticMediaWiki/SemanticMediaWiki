<?php

namespace SMW\DataValues\ValueValidators;

use SMW\Constraint\Constraints\UniqueValueConstraint;
use SMW\PropertySpecificationLookup;
use SMWDataValue as DataValue;

/**
 * @private
 *
 * Only allow values that are unique where uniqueness is establised for the first (
 * in terms of time which also entails that after a full rebuild the first value
 * found is being categorised as established value) value assigned to a property
 * (that requires this trait) and any value that compares to an establised
 * value with the same literal representation is being identified as violating the
 * uniqueness constraint.
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValueValidator implements ConstraintValueValidator {

	/**
	 * @var UniqueValueConstraint
	 */
	private $uniqueValueConstraint;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var bool
	 */
	private $hasConstraintViolation = false;

	/**
	 * @since 2.4
	 *
	 * @param UniqueValueConstraint $uniqueValueConstraint
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( UniqueValueConstraint $uniqueValueConstraint, PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->uniqueValueConstraint = $uniqueValueConstraint;
		$this->propertySpecificationLookup = $propertySpecificationLookup;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation() {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function clear() {
		self::$annotations = [];
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {
		$this->hasConstraintViolation = false;

		if ( !$this->canValidate( $dataValue ) ) {
			return $this->hasConstraintViolation;
		}

		$property = $dataValue->getProperty();

		if ( !$this->propertySpecificationLookup->hasUniquenessConstraint( $property ) ) {
			return $this->hasConstraintViolation;
		}

		$this->uniqueValueConstraint->checkConstraint( [ 'unique_value_constraint' => true ], $dataValue );
		$this->hasConstraintViolation = $this->uniqueValueConstraint->hasViolation();
	}

	private function canValidate( $dataValue ) {
		if ( !$dataValue->isEnabledFeature( SMW_DV_PVUC ) || !$dataValue instanceof DataValue ) {
			return false;
		}

		return $dataValue->getContextPage() !== null && $dataValue->getProperty() !== null;
	}

}
