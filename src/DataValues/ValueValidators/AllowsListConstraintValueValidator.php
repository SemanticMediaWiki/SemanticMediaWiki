<?php

namespace SMW\DataValues\ValueValidators;

use SMW\DataValueFactory;
use SMW\ApplicationFactory;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;
use SMW\Message;
use SMW\DataValues\ValueParsers\AllowsListValueParser;

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
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

	/**
	 * @since 2.4
	 *
	 * @param AllowsListValueParser $allowsListValueParser
	 */
	public function __construct( AllowsListValueParser $allowsListValueParser ) {
		$this->allowsListValueParser = $allowsListValueParser;
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

		if ( !$dataValue instanceof DataValue || $dataValue->getProperty() === null ) {
			return $this->hasConstraintViolation;
		}

		$property = $dataValue->getProperty();
		$propertySpecificationLookup = ApplicationFactory::getInstance()->getPropertySpecificationLookup();

		$allowedValues = $propertySpecificationLookup->getAllowedValuesBy( $property );
		$allowedListValues = $propertySpecificationLookup->getAllowedListValueBy( $property );

		if ( $allowedValues === array() && $allowedListValues === array() ) {
			return $this->hasConstraintViolation;
		}

		$errorList = '';

		$isAllowed = $this->checkOnConstraintViolation(
			$dataValue,
			$allowedValues,
			$errorList
		);

		if ( !$isAllowed ) {
			foreach ( $allowedListValues as $allowedList ) {
				$allowedValues = $this->allowsListValueParser->parse(
					$allowedList->getString()
				);
			}
		}

		$isAllowed = $this->checkOnConstraintViolation(
			$dataValue,
			$allowedValues,
			$errorList
		);

		if ( $isAllowed === false ) {

			$dataValue->addErrorMsg(
				array(
					'smw_notinenum',
					$dataValue->getWikiValue(),
					$errorList,
					$property->getLabel()
				),
				Message::PARSE
			);

			$this->hasConstraintViolation = true;
		}
	}

	private function checkOnConstraintViolation( $dataValue, $allowedValues, &$errorList = '' ) {

		if ( !is_array( $allowedValues ) ) {
			return true;
		}

		$hash = $dataValue->getDataItem()->getHash();

		$testDataValue = ApplicationFactory::getInstance()->getDataValueFactory()->newTypeIDValue(
			$dataValue->getTypeID()
		);

		$isAllowed = false;

		foreach ( $allowedValues as $allowedValue ) {

			if ( is_string( $allowedValue ) ) {
				$allowedValue = new DIBlob( $allowedValue );
			}

			if ( !$allowedValue instanceof DIBlob ) {
				continue;
			}

			$testDataValue->setUserValue( $allowedValue->getString() );

			if ( $hash === $testDataValue->getDataItem()->getHash() ) {
				$isAllowed = true;
				break;
			} else {
				$errorList .= ( $errorList !== '' ? ', ' : '' ) . $allowedValue->getString();
			}
		}

		return $isAllowed;
	}

}
