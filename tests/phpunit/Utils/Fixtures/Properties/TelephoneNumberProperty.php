<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Property;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class TelephoneNumberProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = Property::newFromUserLabel( 'Telephone number' );
		$this->property->setPropertyTypeId( '_tel' );
	}

}
