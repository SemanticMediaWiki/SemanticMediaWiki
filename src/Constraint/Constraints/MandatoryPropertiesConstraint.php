<?php

namespace SMW\Constraint\Constraints;

use RuntimeException;
use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintError;
use SMW\Message;
use SMW\SemanticData;
use SMWDataValue as DataValue;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class MandatoryPropertiesConstraint implements Constraint {

	/**
	 * Defines the expected key in the JSON
	 */
	const CONSTRAINT_KEY = 'mandatory_properties';

	/**
	 * @var bool
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

	private function check( $properties, $dataValue ) {
		$dataItem = $dataValue->getDataItem();
		$properties = array_flip( $properties );

		// PHP 7.0+ $semanticData = $dataValue->getCallable( SemanticData::class )();
		$semanticData = $dataValue->getCallable( SemanticData::class );
		$semanticData = $semanticData();

		foreach ( $semanticData->getProperties() as $property ) {
			unset( $properties[$property->getLabel()] );
		}

		if ( $properties === [] ) {
			return;
		}

		$this->reportError( $dataValue, $properties );
	}

	private function reportError( $dataValue, $properties ) {
		$this->hasViolation = true;

		$error = [
			'smw-constraint-violation-class-mandatory-properties-constraint',
			$dataValue->getWikiValue(),
			implode( ', ', array_keys( $properties ) )
		];

		$dataValue->addError(
			new ConstraintError( $error, Message::PARSE )
		);
	}

}
