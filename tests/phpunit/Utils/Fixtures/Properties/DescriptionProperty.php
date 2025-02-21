<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DIProperty;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class DescriptionProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = new DIProperty( 'Description' );
		$this->property->setPropertyTypeId( '_txt' );
	}

}
