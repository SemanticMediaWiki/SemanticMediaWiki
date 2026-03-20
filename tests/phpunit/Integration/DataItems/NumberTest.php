<?php

namespace SMW\Tests\Integration\DataItems;

use SMW\DataItems\Number;

/**
 * @covers \SMW\DataItems\Number
 * @covers \SMW\DataItems\DataItem
 *
 * @group SMW
 * @group SMWExtension
 * @group DataItems
 * @group NumberTest
 * @group Database
 *
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NumberTest extends AbstractDataItem {

	/**
	 * @see AbstractDataItem::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return Number::class;
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
			[ 0 ],
			[ 243.35353 ],
		];
	}

}
