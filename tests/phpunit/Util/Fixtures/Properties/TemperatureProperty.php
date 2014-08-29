<?php

namespace SMW\Tests\Util\Fixtures\Properties;

use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class TemperatureProperty {

	/**
	 * @var DIProperty
	 */
	private $property = null;

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = new DIProperty( 'Temperature' );
		$this->property->setPropertyTypeId( '_tem' );
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
