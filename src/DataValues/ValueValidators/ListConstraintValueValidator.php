<?php

namespace SMW\DataValues\ValueValidators;

use SMW\DataValueFactory;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ListConstraintValueValidator implements ConstraintValueValidator {

	/**
	 * @var boolean
	 */
	private $hasConstraintViolation = false;

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

		if (
			!$dataValue instanceof DataValue ||
			$dataValue->getProperty() === null ) {
			return $this->hasConstraintViolation;
		}

		$property = $dataValue->getProperty();

		if ( ( $allowedListValues = $dataValue->getPropertySpecificationLookup()->getAllowedValuesFor( $property ) ) === array() ||
			!is_array( $allowedListValues ) ) {
			return $this->hasConstraintViolation;
		}

		$valuestring = '';

		if ( !$this->canMatchAllowedValues( $dataValue, $allowedListValues, $valuestring ) ) {

			$dataValue->addErrorMsg(
				array(
					'smw_notinenum',
					$dataValue->getWikiValue(),
					$valuestring
				)
			);

			$this->hasConstraintViolation = true;
		}
	}

	private function canMatchAllowedValues( $dataValue, $allowedListValues, &$valuestring = '' ) {

		$hash = $dataValue->getDataItem()->getHash();

		$testDataValue = DataValueFactory::getInstance()->newTypeIDValue(
			$dataValue->getTypeID()
		);

		$isAllowed = false;

		foreach ( $allowedListValues as $di ) {
			if ( !$di instanceof DIBlob ) {
				continue;
			}

			$testDataValue->setUserValue( $di->getString() );

			if ( $hash === $testDataValue->getDataItem()->getHash() ) {
				$isAllowed = true;
				break;
			} else {
				$valuestring .= ( $valuestring !== '' ? ', ' : '' ) . $di->getString();
			}
		}

		return $isAllowed;
	}

}
