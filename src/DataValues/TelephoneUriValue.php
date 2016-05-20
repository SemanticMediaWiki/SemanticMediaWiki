<?php

namespace SMW\DataValues;

use SMWURIValue as UriValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TelephoneUriValue extends UriValue {

	/**
	 * @since 2.4
	 *
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '_tel' );
	}

}
