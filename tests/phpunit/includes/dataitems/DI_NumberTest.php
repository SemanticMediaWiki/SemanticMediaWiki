<?php

namespace SMW\Tests;

/**
 * Tests for the SMWDINumber class.
 *
 * @file
 * @since 1.8
 *
 * @ingroup SMW
 * @ingroup Test
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
			array( true, 0 ),
			array( true, 243.35353 ),
			array( false, 'ohi there' ),
			array( false, array() ),
			array( false, true ),
		);
	}

}