<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Property;
use SMW\DataValueFactory;
use SMW\DIWiKiPage;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class CountryCategory {

	/**
	 * @var Property
	 */
	private $property = null;

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = new Property( '_INST' );
	}

	/**
	 * @since 2.1
	 *
	 * @return Property
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
		return DataValueFactory::getInstance()->newDataValueByItem(
			$this->asSubject(),
			$this->getProperty()
		);
	}

}
