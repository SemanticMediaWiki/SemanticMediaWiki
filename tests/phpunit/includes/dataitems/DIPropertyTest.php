<?php

namespace SMW\Tests;

/**
 * @covers \SMW\DIProperty
 * @covers SMWDataItem
 *
 * @group SMW
 * @group SMWExtension
 * @group SMWDataItems
 *
 * @author Nischay Nahata
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DIPropertyTest extends DataItemTest {

	/**
	 * @see DataItemTest::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMWDIProperty';
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
			array( 'ohi there' ),
		);
	}

	/**
	 * @see DataItemTest::invalidConstructorArgsProvider
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function invalidConstructorArgsProvider() {
		return array(
			array( true ),
			array( array() ),
		);
	}

}