<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DIProperty;
use SMW\DIWiKiPage;
use SMW\DataValueFactory;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class CountryCategory {

	/**
	 * @var DIProperty
	 */
	private $property = null;

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = new DIProperty( '_INST' );
	}

	/**
	 * @since 2.1
	 *
	 * @return DIProperty
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @since 2.1
	 *
	 * @return DIWiKiPage
	 */
	public function asSubject() {
		return new DIWiKiPage( 'Country', NS_CATEGORY );
	}

	/**
	 * @since 2.1
	 *
	 * @return DataValue
	 */
	public function getCategoryValue() {
		return DataValueFactory::getInstance()->newDataItemValue(
			$this->asSubject(),
			$this->getProperty()
		);
	}

}
