<?php

namespace SMW\Tests;

/**
 * @covers SMWDIGeoCoord
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
class SMWDIGeoCoordTest extends DataItemTest {

	/**
	 * @see DataItemTest::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMWDIGeoCoord';
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
			array( array( 'lat' => 83.34, 'lon' => 38.44, 'alt' => 54 ) ),
			array( array( 'lat' => 42.43, 'lon' => 33.32 ) ),
		);
	}

}
