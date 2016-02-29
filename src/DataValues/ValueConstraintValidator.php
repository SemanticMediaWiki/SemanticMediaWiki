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
	 * @since 2.4
	 *
	 * @param AllowsPatternValue $allowsPatternValue
	 * @param AllowsListValue $allowsListValue
	 */
	public function __construct( AllowsPatternValue $allowsPatternValue, AllowsListValue $allowsListValue ) {
		$this->allowsPatternValue = $allowsPatternValue;
		$this->allowsListValue = $allowsListValue;
	}

	/**
	 * @since 2.4
	 *
	 * @return ValueConstraintValidator
	 */
	public static function newInstance() {
		return new self(
			new AllowsPatternValue(),
			new AllowsListValue()
		);
	}

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 */
	public function doValidate( DataValue $dataValue ) {

		if ( $dataValue->getProperty() === null || $dataValue->getDataItem() === null ) {
			return;
		}

		$this->allowsPatternValue->clearErrors();
		$this->allowsListValue->clearErrors();

		$this->allowsPatternValue->doCheckAllowedPatternFor(
			$dataValue
		);

		$dataValue->addError( $this->allowsPatternValue->getErrors() );

		if ( $this->allowsPatternValue->getErrors() !== array() ) {
			return;
		}

		$this->allowsListValue->doCheckAllowedValuesFor(
			$dataValue
		);

		$dataValue->addError( $this->allowsListValue->getErrors() );
	}

}
