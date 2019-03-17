<?php

namespace SMW\DataValues;

use SMWURIValue as UriValue;

/**
 * https://www.ietf.org/rfc/rfc3986.txt describes:
 *
 * " ... Uniform Resource Identifier (URI) is a compact sequence of characters
 * that identifies an abstract or physical resource." with "... Uniform Resource
 * Locator" (URL) refers to the subset of URIs that provide a means of locating
 * the resource by describing its primary access mechanism ..."
 *
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

		if ( filter_var( $value, FILTER_VALIDATE_URL ) === false && preg_match( '/((mailto\:|(news|urn|tel|(ht|f)tp(s?))\:\/\/){1}\S+)/u', $value ) === false ) {
			$this->addErrorMsg( [ 'smw-datavalue-external-formatter-invalid-uri', $value ] );
			return;
		}

		if ( strpos( $value, '$1' ) === false ) {
			$this->addErrorMsg( 'smw-datavalue-external-formatter-uri-missing-placeholder' );
			return;
		}

		parent::parseUserValue( $value );
	}

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function hasMultiSubstitute() {

		$dataItem = $this->getDataItem();
		$uri = str_replace( [ '%24' ], [ '$' ], $dataItem->getUri() );

		// Has at least $1 and $2 (...)
		return strpos( $uri, '$1' ) && strpos( $uri, '$2' );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function substituteAndFormatUri( $value, $parameters = [] ) {

		if ( !$this->isValid() ) {
			return '';
		}

		// Convert MediWiki's ` ` as `_`
		$value = str_replace( ' ' , '_', $value );
		$uri = $this->getDataItem()->getUri();

		// Avoid already encoded values like `W%D6LLEKLA01` to be
		// encoded twice
		$value = $this->encode( rawurldecode( $value ) );
		$uri = str_replace( [ '%241', '$1' ], [ '$1', $value ], $uri );

		// Fill the other parameters
		foreach ( $parameters as $key => $val ) {
			$pos = $key + 2;
			$uri = str_replace(
				[ "%24" . $pos, "$" . $pos ],
				[ "$" . $pos, $this->encode( rawurldecode( $val ) ) ],
				$uri
			);
		}

		// %241 == encoded $1
		return $uri;
	}

	// http://php.net/manual/en/function.urlencode.php#97969
	private function encode( $string ) {
		return str_replace(
			[ '%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D' ],
			[ '!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]" ],
			urlencode( $string )
		);
	}
}
