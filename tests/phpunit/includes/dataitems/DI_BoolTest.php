<?php

namespace SMW\Tests;

/**
 * @covers SMWDIBoolean
 * @covers SMWDataItem
 *
 * @file
 * @since 1.8
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 *
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWDIBooleanTest extends DataItemTest {

	/**
	 * @see DataItemTest::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMWDIBoolean';
	}

	/**
	 * @see DataItemTest::constructorProvider
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
