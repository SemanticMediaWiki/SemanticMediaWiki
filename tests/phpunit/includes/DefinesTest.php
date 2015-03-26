<?php

namespace SMW\Test;

/**
 * Tests for global constants being loaded
 *
 * @since 1.9
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for global constants being loaded
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class DefinesTest extends  SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|boolean
	 */
	public function getClass() {
		return false;
	}

	/**
	 * Provides sample of constants to be tested
	 *
	 * @return array
	 */
	public function getConstantsDataProvider() {
		return array(
			array( SMW_HEADERS_SHOW, 2 ),
			array( SMW_HEADERS_PLAIN, 1 ),
			array( SMW_HEADERS_HIDE, 0 ),
			array( SMW_OUTPUT_HTML, 1 ),
			array( SMW_OUTPUT_WIKI, 2 ),
			array( SMW_OUTPUT_FILE, 3 ),
			array( SMW_FACTBOX_HIDDEN, 1 ),
			array( SMW_FACTBOX_SPECIAL, 2 ),
			array( SMW_FACTBOX_NONEMPTY, 3 ),
			array( SMW_FACTBOX_SHOWN, 5 ),
		);
	}

	/**
	 * Test if constants are accessible
	 * @dataProvider getConstantsDataProvider
	 *
	 * @param $constant
	 * @param $expected
	 */
	public function testConstants( $constant, $expected ) {
		$this->assertEquals( $expected, $constant );
	}
}
