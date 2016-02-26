<?php

namespace SMW\DataValues;

use SMW\DataValueFactory;
use SMWStringValue as StringValue;
use SMWDIBlob as DIBlob;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsValue extends StringValue {

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '__pval' );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	public function getAllowedValuesFor( DIProperty $property ) {
		return $this->getPropertySpecificationLookup()->getAllowedValuesFor( $property );
	}

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 */
	public function isAllowedValueFor( DataValue $dataValue ) {

		$property = $dataValue->getProperty();

		if ( ( $allowedValues = $this->getAllowedValuesFor( $property ) ) === array() || !is_array( $allowedValues ) ) {
			return null;
		}

		$valuestring = '';

		if ( !$this->testAllowedValues( $dataValue, $allowedValues, $valuestring ) ) {
			$this->addErrorMsg( array( 'smw_notinenum', $dataValue->getWikiValue(), $valuestring ) );
		}

		return $this->getErrors() === array();
	}

	private function testAllowedValues( $dataValue, $allowedValues, &$valuestring = '' ) {

		$hash = $dataValue->getDataItem()->getHash();

		$testDataValue = DataValueFactory::getInstance()->newTypeIDValue(
			$dataValue->getTypeID()
		);

		$isAllowed = false;

		foreach ( $allowedValues as $di ) {
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
