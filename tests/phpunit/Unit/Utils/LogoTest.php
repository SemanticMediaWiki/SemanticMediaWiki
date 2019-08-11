<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Logo;

/**
 * @covers \SMW\Utils\Logo
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class LogoTest extends \PHPUnit_Framework_TestCase {

	public function testGet_Small() {

		$this->assertContains(
			'data:image/png;base64',
			Logo::get( '100x90' )
		);

		$this->assertContains(
			'data:image/png;base64',
			Logo::get( 'small' )
		);
	}

	public function testGet_Footer() {

		$this->assertContains(
			'data:image/png;base64',
			Logo::get( 'footer' )
		);
	}

	public function testGet_Unkown() {

		$this->assertNull(
			Logo::get( 'Foo' )
		);
	}

}
