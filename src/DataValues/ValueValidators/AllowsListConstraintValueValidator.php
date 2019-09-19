<?php

namespace SMW\DataValues\ValueValidators;

use SMW\ApplicationFactory;
use SMW\DataValues\ValueParsers\AllowsListValueParser;
use SMW\PropertySpecificationLookup;
use SMW\Message;
use SMWDataValue as DataValue;
use SMWNumberValue as NumberValue;
use SMWDIBlob as DIBlob;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsListConstraintValueValidator implements ConstraintValueValidator {

	/**
	 * @var AllowsListValueParser
	 */
	private $allowsListValueParser;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @var string
	 */
	private $errorMsg = '';

	/**
	 * @since 2.4
	 *
	 * @param AllowsListValueParser $allowsListValueParser
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( AllowsListValueParser $allowsListValueParser, PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->allowsListValueParser = $allowsListValueParser;
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
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function validate( $dataValue ) {

		$this->hasConstraintViolation = false;
		$this->errorMsg = 'smw-datavalue-constraint-error-allows-value-list';

		if ( !$dataValue instanceof DataValue || $dataValue->getProperty() === null ) {
			return $this->hasConstraintViolation;
		}

		$property = $dataValue->getProperty();

		$allowedValues = $this->propertySpecificationLookup->getAllowedValues(
			$property
		);

		$allowedListValues = $this->propertySpecificationLookup->getAllowedListValues(
			$property
		);

		if ( $allowedValues === [] && $allowedListValues === [] ) {
			return $this->hasConstraintViolation;
		}

		$allowedValueList = [];

		$isAllowed = $this->checkConstraintViolation(
			$dataValue,
			$allowedValues,
			$allowedValueList
		);


		if ( !$isAllowed ) {
			foreach ( $allowedListValues as $dataItem ) {
				$list = $this->allowsListValueParser->parse( $dataItem->getString() );

				// Combine different lists into one
				if ( is_array( $list ) ) {
					$allowedValues = array_merge( $allowedValues, $list );
				}
			}

			// On assignments like [Foo => Foo] (* Foo) or [Foo => Bar] (* Foo|Bar)
			// use the key as comparison entity
			$allowedValues = array_keys( $allowedValues );
		} else {
			return;
		}

		$isAllowed = $this->checkConstraintViolation(
			$dataValue,
			$allowedValues,
			$allowedValueList
		);

		if ( $isAllowed === true ) {
			return;
		}

		$count = count( $allowedValueList );

		// Only the first 10 values otherwise the list may become too long
		$allowedValueList = implode( ', ', array_slice(
			array_keys( $allowedValueList ), 0 , 10 )
		);

		$allowedValueList = str_replace( [ '>', '<' ], [ '%3C', '%3E' ], $allowedValueList );

		$dataValue->addErrorMsg(
			[
				$this->errorMsg,
				$dataValue->getWikiValue(),
				$allowedValueList . ( $count > 10 ? ', ...' : '' ),
				$property->getLabel()
			],
			Message::PARSE
		);

		$this->hasConstraintViolation = true;
	}

	private function checkConstraintViolation( $dataValue, $allowedValues, &$allowedValueList ) {

		if ( !is_array( $allowedValues ) ) {
			return true;
		}

		$hash = $dataValue->getDataItem()->getHash();
		$value = $dataValue->getWikiValue();

		$testDataValue = ApplicationFactory::getInstance()->getDataValueFactory()->newTypeIDValue(
			$dataValue->getTypeID()
		);

		// Ensure that the validation instance uses the same field properties
		// as defined by the original DataValue
		if ( $dataValue instanceof \SMW\DataValues\AbstractMultiValue ) {
			$testDataValue->setFieldProperties( $dataValue->getPropertyDataItems() );
		}

		$isAllowed = false;

		// Track a range related constraint which can be used as single
		// `[[Allows value::>0]]` assignment or as conjunctive compound as in
		// `[[Allows value::>0]] [[Allows value::<100]]` and can appear in any
		// order
		$range = null;

		foreach ( $allowedValues as $allowedValue ) {

			if ( is_string( $allowedValue ) ) {
				$allowedValue = new DIBlob( $allowedValue );
			}

			if ( !$allowedValue instanceof DIBlob ) {
				continue;
			}

			if ( $testDataValue instanceof NumberValue ) {

				// Check [[Allows value::>0]]
				if ( $this->check_range( '>', $value, $allowedValue, $range, $isAllowed, $allowedValueList ) ) {
					continue;
				}

				// Check [[Allows value::<100]]
				if ( $this->check_range( '<', $value, $allowedValue, $range, $isAllowed, $allowedValueList ) ) {
					continue;
				}

				// Check [[Allows value::1...100]]
				if ( $this->check_bounds( $value, $allowedValue, $isAllowed, $allowedValueList ) ) {
					break;
				}
			}

			// For a time based range one could use the JD date and simply apply
			// a >, < comparison on something like `[[Allows value::>1970]]
			// [[Allows value::<31.12.2100]]`

			// String range based constraints seems to make not much sense for
			// something like `[[Allows value::>abc]] [[Allows value::<def]]`

			$testDataValue->setUserValue( $allowedValue->getString() );

			if ( $hash === $testDataValue->getDataItem()->getHash() ) {
				$isAllowed = true;
				break;
			} else {
				// Filter dups
				$allowedValueList[$allowedValue->getString()] = true;
			}
		}

		return $isAllowed;
	}

	private function check_range( $exp, $value, $allowedValue, &$range, &$isAllowed, &$allowedValueList ) {

		$v = $allowedValue->getString();

		// If a previous range comparison failed then bail-out!
		if ( $v[0] === $exp && ( $range === null || $range ) ) {
			$v = intval( trim( substr( $v, 1 ) ) );

			if ( $exp === '>' && $value > $v ) {
				$isAllowed = true;
			} elseif ( $exp === '<' && $value < $v ) {
				$isAllowed = true;
			} else {
				$isAllowed = false;
				$range = false;
			}

			if ( $range === false ) {
				$allowedValueList[$allowedValue->getString()] = true;
			}

			return true;
		}

		$this->errorMsg = 'smw-datavalue-constraint-error-allows-value-range';

		return false;
	}

	private function check_bounds( $value, $allowedValue, &$isAllowed, &$allowedValueList ) {

		$v = $allowedValue->getString();

		if ( strpos( $v, '...' ) === false ) {
			return false;
		}

		list( $lower, $upper ) = explode( '...', $v );

		if ( $value >= intval( $lower ) && $value <= intval( $upper ) ) {
			return $isAllowed = true;
		} else {
			$allowedValueList[$allowedValue->getString()] = true;
		}

		$this->errorMsg = 'smw-datavalue-constraint-error-allows-value-range';

		return false;
	}

}
