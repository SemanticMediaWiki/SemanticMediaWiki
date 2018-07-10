<?php

namespace SMW\Tests;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DefinesTest extends  \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constantsDataProvider
	 */
	public function testConstants( $constant, $expected ) {
		$this->assertEquals( $expected, $constant );
	}

	public function constantsDataProvider() {
		return [
			[ SMW_HEADERS_SHOW, 2 ],
			[ SMW_HEADERS_PLAIN, 1 ],
			[ SMW_HEADERS_HIDE, 0 ],
			[ SMW_OUTPUT_HTML, 1 ],
			[ SMW_OUTPUT_WIKI, 2 ],
			[ SMW_OUTPUT_FILE, 3 ],
			[ SMW_FACTBOX_HIDDEN, 1 ],
			[ SMW_FACTBOX_SPECIAL, 2 ],
			[ SMW_FACTBOX_NONEMPTY, 3 ],
			[ SMW_FACTBOX_SHOWN, 5 ],
		];
	}

}
