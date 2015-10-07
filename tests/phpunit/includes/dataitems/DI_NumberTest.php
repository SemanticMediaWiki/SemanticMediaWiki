<?php

namespace SMW\Tests;

/**
 * @covers SMWDINumber
 * @covers SMWDataItem
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 * @group SMWDINumberTest
 *
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWDINumberTest extends DataItemTest {

	/**
	 * @see DataItemTest::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMWDINumber';
	}

	/**
	 * @see DataItemTest::constructorProvider
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		return array(
			array( 0 ),
			array( 243.35353 ),
		);
	}

}
