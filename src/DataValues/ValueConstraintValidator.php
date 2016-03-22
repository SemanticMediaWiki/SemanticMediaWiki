<?php

namespace SMW\DataValues;

use SMWDataValue as DataValue;
use SMW\DataValueFactory;
use SMW\DIProperty;

/**
 * Validates whether a DataValue (property, value) is restricted by some constraints
 * that have been specified using meta-properties.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ValueConstraintValidator {

	/**
	 * @var AllowsPatternValue
	 */
	private $allowsPatternValue;

	/**
	 * @var AllowsListValue
	 */
	private $allowsListValue;

	/**
	 * @var UniquenessConstraintValue
	 */
	private $uniquenessConstraintValue;

	/**
	 * @since 2.4
	 *
	 * @param AllowsPatternValue $allowsPatternValue
	 * @param AllowsListValue $allowsListValue
	 * @param UniquenessConstraintValue $uniquenessConstraintValue
	 */
	public function __construct( AllowsPatternValue $allowsPatternValue, AllowsListValue $allowsListValue, UniquenessConstraintValue $uniquenessConstraintValue ) {
		$this->allowsPatternValue = $allowsPatternValue;
		$this->allowsListValue = $allowsListValue;
		$this->uniquenessConstraintValue = $uniquenessConstraintValue;
	}

	/**
	 * @note Static access is done to improve performance during execution of the
	 * DataValueFactory::getValueConstraintValidator and avoids that for each
	 * DV request a new instance is created.
	 *
	 * @since 2.4
	 *
	 * @return ValueConstraintValidator
	 */
	public static function newInstance() {
		return new self(
			new AllowsPatternValue(),
			new AllowsListValue(),
			new UniquenessConstraintValue()
		);
	}

	/**
	 * @see DataValue::checkAllowedValues
	 *
	 * Any error produced during one of the checks (UniquenessConstraint,
	 * AllowedPattern, AllowedValues) will yield an immediate processing stop
	 * for a value assignment that has been categorized as not suitable in
	 * context of the expressed constraints.
	 *
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 */
	public function doValidate( DataValue $dataValue ) {

		if ( $dataValue->getProperty() === null || $dataValue->getDataItem() === null ) {
			return;
		}

		$this->uniquenessConstraintValue->clearErrors();

		$this->uniquenessConstraintValue->doCheckUniquenessConstraintFor(
			$dataValue
		);

		$dataValue->addError(
			$this->uniquenessConstraintValue->getErrors()
		);

		if ( $this->uniquenessConstraintValue->getErrors() !== array() ) {
			return;
		}

		$this->allowsPatternValue->clearErrors();

		$this->allowsPatternValue->doCheckAllowedPatternFor(
			$dataValue
		);

		$dataValue->addError(
			$this->allowsPatternValue->getErrors()
		);

		if ( $this->allowsPatternValue->getErrors() !== array() ) {
			return;
		}

		$this->allowsListValue->clearErrors();

		$this->allowsListValue->doCheckAllowedValuesFor(
			$dataValue
		);

		$dataValue->addError(
			$this->allowsListValue->getErrors()
		);
	}

}
