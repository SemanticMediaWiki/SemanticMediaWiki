<?php

namespace SMW\Tests;

/**
 * @covers SMWDINumber
 * @covers SMWDataItem
 *
 * @file
 * @since 1.8
 *
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
			array( 'abc' ),
		);
	}

}