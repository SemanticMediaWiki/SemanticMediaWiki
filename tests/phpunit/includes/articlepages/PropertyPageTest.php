<?php

namespace SMW\Test;

use SMWPropertyPage as PropertyPage;

use Title;

/**
 * @covers \SMWPropertyPage
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class PropertyPageTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMWPropertyPage',
			new PropertyPage( Title::newFromText( 'Foo', SMW_NS_PROPERTY ) )
		);
	}

}
