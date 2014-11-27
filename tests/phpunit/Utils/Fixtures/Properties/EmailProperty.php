<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class EmailProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = DIProperty::newFromUserLabel( 'Email' );
		$this->property->setPropertyTypeId( '_ema' );
	}

}
