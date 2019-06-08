<?php

namespace SMW\Constraint\Constraints;

use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintError;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class NonNegativeIntegerConstraint implements Constraint {

	/**
	 * @var boolean
	 */
	private $hasViolation = false;

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasViolation() {
		return $this->hasViolation;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getType() {
		return Constraint::TYPE_INSTANT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function checkConstraint( array $constraint, $dataValue ) {

		$this->hasViolation = false;

		if ( !$dataValue instanceof DataValue ) {
			throw new RuntimeException( "Expected a DataValue instance!" );
		}

		$key = key( $constraint );

		if ( isset( $constraint['non_negative_integer'] ) && $constraint['non_negative_integer'] ) {
			$this->check( $dataValue );
		}
	}

	private function check( $dataValue ) {

		$dataItem = $dataValue->getDataItem();

		if ( $dataItem->getDIType() !== DataItem::TYPE_NUMBER ) {
			return;
		}

		// https://www.w3.org/TR/xmlschema11-2/#nonNegativeInteger
		if ( ( $number = $dataItem->getNumber() ) >= 0 ) {
			return;
		}

		$this->reportError( $dataValue, $number );
	}

	private function reportError( $dataValue, $number ) {

		$this->hasViolation = true;

		$dataValue->addError( new ConstraintError( [
				'smw-datavalue-constraint-violation-non-negative-integer',
				$dataValue->getProperty()->getLabel(),
				$number
			] )
		);
	}

}
