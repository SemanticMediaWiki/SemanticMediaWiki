<?php

namespace SMW\Tests\Utils\Fixtures\Properties;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWiKiPage;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class CityCategory extends FixtureProperty {

	/**
	 * @since 2.1
	 */
	public function __construct() {
		$this->property = new DIProperty( '_INST' );
	}

	/**
	 * @since 2.1
	 *
	 * @return DIWiKiPage
	 */
	public function asSubject() {
		return new DIWiKiPage( 'City', NS_CATEGORY );
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
