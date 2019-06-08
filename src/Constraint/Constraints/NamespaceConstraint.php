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

		if ( $key === 'allowed_namespaces' ) {
			return $this->check( $constraint[$key], $dataValue );
		}
	}

	private function check( $namespaces, $dataValue ) {

		$dataItem = $dataValue->getDataItem();

		if ( $dataItem->getDIType() !== DataItem::TYPE_WIKIPAGE ) {

			$error = [
				'smw-constraint-schema-violation-requires-page-type'
			];

			return $this->reportError( $dataValue, $error );
		}

		foreach ( $namespaces as $ns ) {
			if ( defined( $ns ) && constant( $ns ) == $dataItem->getNamespace() ) {
				return;
			}
		}

		$error = [
			'smw-constraint-schema-violation-allowed-namespace-no-match',
			$dataValue->getWikiValue(),
			implode(', ', $namespaces ),
			$dataValue->getProperty()->getLabel()
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
