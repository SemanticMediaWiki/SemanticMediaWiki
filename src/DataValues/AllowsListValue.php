<?php

namespace SMW\DataValues;

use SMW\DataValueFactory;
use SMW\ApplicationFactory;
use SMWStringValue as StringValue;
use SMWDIBlob as DIBlob;
use SMW\DIProperty;
use SMWDataValue as DataValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class AllowsListValue extends StringValue {

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '__pval' );
	}

}
