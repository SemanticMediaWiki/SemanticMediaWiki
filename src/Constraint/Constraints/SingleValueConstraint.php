<?php

namespace SMW\Constraint\Constraints;

use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintError;
use SMW\Message;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMW\SemanticData;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SingleValueConstraint implements Constraint {

	/**
	 * Defines the expected key in the JSON
	 */
	const CONSTRAINT_KEY = 'single_value_constraint';

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

		if ( $key === self::CONSTRAINT_KEY ) {
			return $this->check( $constraint[$key], $dataValue );
		}
	}

	private function check( $single_value, $dataValue ) {

		if ( $single_value === false ) {
			return;
		}

		// PHP 7.0+
		// $semanticData = $dataValue->getCallable( SemanticData::class )();
		$semanticData = $dataValue->getCallable( SemanticData::class );
		$semanticData = $semanticData();

		$property = $dataValue->getProperty();
		$dataItems = $semanticData->getPropertyValues( $property );

		if ( count( $dataItems ) >= 1 ) {
			$this->reportError( $dataValue, $property );
		}
	}

	private function reportError( $dataValue, $property ) {

		$this->hasViolation = true;

		$error = [
			'smw-datavalue-constraint-violation-single-value',
			$property->getLabel(),
			$dataValue->getWikiValue()
		];

		$dataValue->addError(
			new ConstraintError( $error, Message::PARSE )
		);
	}

}
