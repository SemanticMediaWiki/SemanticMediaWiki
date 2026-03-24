<?php

namespace SMW\Constraint\Constraints;

use RuntimeException;
use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintError;
use SMW\DataModel\SemanticData;
use SMW\DataValues\DataValue;
use SMW\Localizer\Message;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SingleValueConstraint implements Constraint {

	/**
	 * Defines the expected key in the JSON
	 */
	const CONSTRAINT_KEY = 'single_value_constraint';

	private bool $hasViolation = false;

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasViolation(): bool {
		return $this->hasViolation;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getType(): string {
		return Constraint::TYPE_INSTANT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function checkConstraint( array $constraint, $dataValue ): void {
		$this->hasViolation = false;

		if ( !$dataValue instanceof DataValue ) {
			throw new RuntimeException( "Expected a DataValue instance!" );
		}

		$key = key( $constraint );

		if ( $key === self::CONSTRAINT_KEY ) {
			$this->check( $constraint[$key], $dataValue );
			return;
		}
	}

	private function check( $single_value, DataValue $dataValue ): void {
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

	private function reportError( DataValue $dataValue, $property ): void {
		$this->hasViolation = true;

		$error = [
			'smw-constraint-violation-single-value',
			$property->getLabel(),
			$dataValue->getWikiValue()
		];

		$dataValue->addError(
			new ConstraintError( $error, Message::PARSE )
		);
	}

}
