<?php

namespace SMW\DataValues;

use SMWURIValue as UriValue;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExternalFormatterUriValue extends UriValue {

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '__peru' );
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addErrorMsg( 'smw_emptystring' );
			return;
		}

		if ( filter_var( $value, FILTER_VALIDATE_URL ) === false ) {
			$this->addErrorMsg( array( 'smw-datavalue-external-formatter-invalid-uri', $value ) );
			return;
		}

		if ( strpos( $value, '$1' ) === false ) {
			$this->addErrorMsg( 'smw-datavalue-external-formatter-uri-missing-placeholder' );
			return;
		}

		parent::parseUserValue( $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function getFormattedUriWith( $value ) {
		// %241 == encoded $1
		return str_replace( array( '%241', '$1' ), array( '$1', rawurlencode( $value ) ), $this->getDataItem()->getUri() );
	}

}
