<?php

namespace SMW\Tests\Utils;

use PHPUnit\Framework\TestCase;
use SMW\Utils\Logo;

/**
 * @covers \SMW\Utils\Logo
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class LogoTest extends TestCase {

	public function testGet_Small() {
		$this->assertStringContainsString(
			'assets/logo_small.svg',
			Logo::get( 'small' )
		);
	}

	public function testGet_Footer() {
		$this->assertStringContainsString(
			'assets/logo_footer.svg',
			Logo::get( 'footer' )
		);
	}

	public function testGet_Unknown() {
		$this->assertNull(
			Logo::get( 'Foo' )
		);
	}

}
