<?php

namespace SMW\Constraint\Constraints;

use SMW\Constraint\Constraint;
use SMW\Constraint\ConstraintError;
use SMW\PropertySpecificationLookup;
use SMW\Store;
use SMw\RequestOptions;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use RuntimeException;

/**
 * The `unique_value_constraint` implicitly requires a `GLOBAL_SCOPE` (instead
 * of only an `ENTITY_SCOPE` which would just require a `single_value_constraint`).
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UniqueValueConstraint implements Constraint {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var boolean
	 */
	private $hasViolation = false;

	/**
	 * Tracks annotations for the current context to verify that a subject only
	 * contains unique assignments.
	 *
	 * @var array
	 */
	private $annotations = [];

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( Store $store, PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->store = $store;
		$this->propertySpecificationLookup = $propertySpecificationLookup;
	}

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

		if ( isset( $constraint['unique_value_constraint'] ) && $constraint['unique_value_constraint'] ) {
			$this->check( $dataValue );
		}
	}

	private function check( $dataValue ) {

		$property = $dataValue->getProperty();
		$contextPage = $dataValue->getContextPage();

		if ( $contextPage === null || $property === null ) {
			return;
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setCaller( __METHOD__ );

		// Exclude the current page from the result match to check whether another
		// page matches the condition and if so then the value can no longer be
		// assigned and is not unique
		$requestOptions->addExtraCondition( function( $store, $query, $alias ) use( $contextPage ) {
				return $query->neq( "$alias.s_id", $store->getObjectIds()->getId( $contextPage ) );
			}
		);

		$requestOptions->setLimit( 2 );
		$count = 0;

		if ( !$this->hasAnnotation( $dataValue ) ) {
			$entityUniquenessLookup = $this->store->service( 'EntityUniquenessLookup' );

			$res = $entityUniquenessLookup->checkConstraint(
				$property,
				$dataValue->getDataItem(),
				$requestOptions
			);

			$count = count( $res );
		}

		// Check whether the current page has any other annotation for the
		// same property
		if ( $count < 1 && $this->isKnown( $dataValue ) ) {
			$error = [
				'smw-datavalue-constraint-uniqueness-violation-isknown',
				$property->getLabel(),
				$contextPage->getTitle()->getPrefixedText(),
				$dataValue->getWikiValue()
			];

			$this->reportError( $dataValue, $error );
		}

		// Has the page different values for the same property?
		if ( $count < 1 ) {
			return;
		}

		foreach ( $res as $dataItem ) {
			$val = $dataValue->isValid() ? $dataValue->getWikiValue() : '...';
			$text = '';

			if ( $dataItem !== null && ( $title = $dataItem->getTitle() ) !== null ) {
				$text = $title->getPrefixedText();
			}

			$error = [
				'smw-datavalue-constraint-uniqueness-violation',
				$property->getLabel(),
				$val,
				$text
			];

			$this->reportError( $dataValue, $error );
		}
	}

	private function isKnown( $dataValue ) {

		$contextPage = $dataValue->getContextPage();
		$dataItem = $dataValue->getDataItem();
		$property = $dataValue->getProperty();

		$valueHash = md5( $property->getKey() . $dataItem->getHash() );
		$key = $property->getKey();
		$hash = $contextPage->getHash();

		if ( isset( $this->annotations[$hash][$key] ) && $this->annotations[$hash][$key] !== $valueHash ) {
			return true;
		} else {
			$this->annotations[$hash][$key] = $valueHash;
		}

		return false;
	}

	private function hasAnnotation( $dataValue ) {

		$key = $dataValue->getProperty()->getKey();
		$hash = $dataValue->getContextPage()->getHash();

		return isset( $this->annotations[$hash][$key] );
	}

	private function reportError( $dataValue, $error ) {
		$this->hasViolation = true;

		$dataValue->addError(
			new ConstraintError( $error )
		);
	}

}
