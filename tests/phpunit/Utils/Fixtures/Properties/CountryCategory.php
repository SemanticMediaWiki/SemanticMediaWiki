<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;

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
	 * @return WikiPage
	 */
	public function asSubject() {
		return new WikiPage( 'Country', NS_CATEGORY );
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
