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
class NamespaceConstraint implements Constraint {

	/**
	 * Defines the expected key in the JSON
	 */
	const CONSTRAINT_KEY = 'allowed_namespaces';

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

	private function check( $namespaces, $dataValue ) {

		$dataItem = $dataValue->getDataItem();
		$property = $dataValue->getProperty();

		if ( $dataItem->getDIType() !== DataItem::TYPE_WIKIPAGE ) {

			$error = [
				'smw-constraint-violation-allowed-namespaces-requires-page-type'
			];

			return $this->reportError( $dataValue, $error );
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

	private function reportError( $dataValue, $error ) {

		$this->hasViolation = true;

		$dataValue->addError(
			new ConstraintError( $error )
		);
	}

}
