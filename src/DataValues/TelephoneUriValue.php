<?php

namespace SMW\DataValues;

use SMWURIValue as UriValue;

/**
 * @license GPL-2.0-or-later
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
