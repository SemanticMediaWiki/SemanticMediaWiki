<?php

namespace SMW\DataValues\ValueValidators;

use SMW\PropertySpecificationLookup;
use SMW\DIWikiPage;
use SMWDataValue as DataValue;
use SMW\RequestOptions;
use SMW\Store;

/**
 * @private
 *
 * Only allow values that are unique where uniqueness is establised for the first (
 * in terms of time which also entails that after a full rebuild the first value
 * found is being categorised as established value) value assigned to a property
 * (that requires this trait) and any value that compares to an establised
 * value with the same literal representation is being identified as violating the
 * uniqueness constraint.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValueValidator implements ConstraintValueValidator {

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
	private $hasConstraintViolation = false;

	/**
	 * Tracks annotations for the current context to verify that a subject only
	 * contains unique assignments.
	 *
	 * @var array
	 */
	private static $annotations = [];

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
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function hasConstraintViolation() {
		return $this->hasConstraintViolation;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function clear() {
		self::$annotations = [];
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {

		$this->hasConstraintViolation = false;

		if ( !$this->canValidate( $dataValue ) ) {
			return $this->hasConstraintViolation;
		}

		$property = $dataValue->getProperty();

		if ( !$this->propertySpecificationLookup->hasUniquenessConstraint( $property ) ) {
			return $this->hasConstraintViolation;
		}

		$contextPage = $dataValue->getContextPage();

		// Exclude the current page from the result match to check whether another
		// page matches the condition and if so then the value can no longer be
		// assigned and is not unique
		$requestOptions = new RequestOptions();

		$requestOptions->addExtraCondition( function( $store, $query, $alias ) use( $contextPage ) {
				return $query->neq( "$alias.s_id", $store->getObjectIds()->getId( $contextPage ) );
			}
		);

		$requestOptions->setLimit( 2 );
		$count = 0;

		if ( !$this->hasAnnotation( $dataValue ) ) {
			$entityValueUniquenessConstraintChecker = $this->store->service( 'EntityValueUniquenessConstraintChecker' );

			$res = $entityValueUniquenessConstraintChecker->checkConstraint(
				$property,
				$dataValue->getDataItem(),
				$requestOptions
			);

			$count = count( $res );
		}

		// Check whether the current page has any other annotation for the
		// same property
		if ( $count < 1 && $this->isRegistered( $dataValue ) ) {
			$dataValue->addErrorMsg(
				[
					'smw-datavalue-uniqueness-constraint-isknown',
					$property->getLabel(),
					$contextPage->getTitle()->getPrefixedText(),
					$dataValue->getWikiValue()
				]
			);

			$this->hasConstraintViolation = true;
		}

		// Has the page different values for the same property?
		if ( $count < 1 ) {
			return $this->hasConstraintViolation;
		}

		$this->hasConstraintViolation = true;

		foreach ( $res as $dataItem ) {
			$val = $dataValue->isValid() ? $dataValue->getWikiValue() : '...';
			$text = '';

			if ( $dataItem !== null && ( $title = $dataItem->getTitle() ) !== null ) {
				$text = $title->getPrefixedText();
			}

			$dataValue->addErrorMsg(
				[
					'smw-datavalue-uniqueness-constraint-error',
					$property->getLabel(),
					$val,
					$text
				]
			);
		}

		return $this->hasConstraintViolation;
	}

	private function canValidate( $dataValue ) {

		if ( !$dataValue->isEnabledFeature( SMW_DV_PVUC ) || !$dataValue instanceof DataValue ) {
			return false;
		}

		return $dataValue->getContextPage() !== null && $dataValue->getProperty() !== null;
	}

	private function isRegistered( $dataValue ) {

		$contextPage = $dataValue->getContextPage();
		$dataItem = $dataValue->getDataItem();
		$property = $dataValue->getProperty();

		$valueHash = md5( $property->getKey() . $dataItem->getHash() );
		$key = $property->getKey();
		$hash = $contextPage->getHash();

		if ( isset( self::$annotations[$hash][$key] ) && self::$annotations[$hash][$key] !== $valueHash ) {
			return true;
		} else {
			self::$annotations[$hash][$key] = $valueHash;
		}

		return false;
	}

	private function hasAnnotation( $dataValue ) {

		$key = $dataValue->getProperty()->getKey();
		$hash = $dataValue->getContextPage()->getHash();

		return isset( self::$annotations[$hash][$key] );
	}

}
