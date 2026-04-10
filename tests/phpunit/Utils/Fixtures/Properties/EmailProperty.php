<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Property;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class EmailProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = Property::newFromUserLabel( 'Email' );
		$this->property->setPropertyTypeId( '_ema' );
	}

}
