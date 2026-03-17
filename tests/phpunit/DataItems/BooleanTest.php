<?php

namespace SMW\Tests;

use SMW\DataItems\Boolean;

/**
 * @covers \SMW\DataItems\Boolean
 * @covers \SMW\DataItems\DataItem
 *
 * @since 1.8
 *
 * @group SMW
 * @group SMWExtension
 * @group DataItems
 * @group Database
 *
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BooleanTest extends AbstractDataItem {

	/**
	 * @see AbstractDataItem::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return Boolean::class;
	}

	/**
	 * @see AbstractDataItem::constructorProvider
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return [
			[ false ],
			[ true ],
		];
	}

}
