<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\SemanticData;
use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class TitleProperty extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = DIProperty::newFromUserLabel( 'Title' );
		$this->property->setPropertyTypeId( '_wpg' );
	}

}
