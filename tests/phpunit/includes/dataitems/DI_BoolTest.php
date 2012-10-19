<?php

namespace SMW\Tests;

/**
 * Tests for the SMWDIBoolean class.
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
		return array(
			array( true, false ),
			array( true, true ),
			array( false, 42 ),
			array( false, array() ),
			array( false, 'ohi there' ),
		);
	}

}
