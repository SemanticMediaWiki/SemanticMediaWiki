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
class MustExistsConstraint implements Constraint {

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

		if ( $key === 'must_exists' ) {
			return $this->check( $constraint[$key], $dataValue );
		}
	}

	private function check( $must_exists, $dataValue ) {

		$dataItem = $dataValue->getDataItem();

		if ( $must_exists === false || $dataItem->getDIType() !== DataItem::TYPE_WIKIPAGE ) {
			return;
		}

		if ( $dataItem->getTitle()->exists() ) {
			return;
		}

		$this->reportError( $dataValue );
	}

	private function reportError( $dataValue ) {

		$this->hasViolation = true;

		$dataValue->addError(
			new ConstraintError( [
				'smw-datavalue-constraint-violation-must-exists',
				$dataValue->getProperty()->getLabel(),
				$dataValue->getWikiValue()
			] )
		);
	}

}
