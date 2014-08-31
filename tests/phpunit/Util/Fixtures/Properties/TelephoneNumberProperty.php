<?php

namespace SMW\Tests\Util\Fixtures\Properties;

use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class TelephoneNumberProperty {

	/**
	 * @var DIProperty
	 */
	private $property = null;

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = DIProperty::newFromUserLabel( 'Telephone number' );
		$this->property->setPropertyTypeId( '_tel' );
	}

	/**
	 * @since 2.1
	 *
	 * @return DIProperty
	 */
	public function getProperty() {
		return $this->property;
	}

}
