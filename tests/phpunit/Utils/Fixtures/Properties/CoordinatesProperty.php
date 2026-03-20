<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Property;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class CoordinatesProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = new Property( 'Coordinates' );
		$this->property->setPropertyTypeId( '_geo' );
	}

}
