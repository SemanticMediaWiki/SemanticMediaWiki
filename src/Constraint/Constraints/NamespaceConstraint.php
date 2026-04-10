<?php

namespace SMW\Constraint\Constraints;

use RuntimeException;
use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintError;
use SMW\DataItems\DataItem;
use SMW\DataValues\DataValue;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class NamespaceConstraint implements Constraint {

	/**
	 * Defines the expected key in the JSON
	 */
	const CONSTRAINT_KEY = 'allowed_namespaces';

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

	private function check( $namespaces, DataValue $dataValue ): void {
		$dataItem = $dataValue->getDataItem();
		$property = $dataValue->getProperty();

		if ( $dataItem->getDIType() !== DataItem::TYPE_WIKIPAGE ) {

			$error = [
				'smw-constraint-violation-allowed-namespaces-requires-page-type'
			];

			$this->reportError( $dataValue, $error );
			return;
		}

		foreach ( $namespaces as $ns ) {
			if ( defined( $ns ) && constant( $ns ) == $dataItem->getNamespace() ) {
				return;
			}
		}

		$error = [
			'smw-constraint-violation-allowed-namespace-no-match',
			$property->getLabel(),
			$dataValue->getWikiValue(),
			implode( ', ', $namespaces )
		];

		$this->reportError( $dataValue, $error );
	}

	private function reportError( DataValue $dataValue, array $error ): void {
		$this->hasViolation = true;

		$dataValue->addError(
			new ConstraintError( $error )
		);
	}

}
