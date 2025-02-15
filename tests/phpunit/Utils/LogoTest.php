<?php

namespace SMW\Tests\Utils;

use SMW\Tests\PHPUnitCompat;
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
class LogoTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testGet_Small() {
		$this->assertContains(
			'assets/logo_small.svg',
			Logo::get( 'small' )
		);
	}

	public function testGet_Footer() {
		$this->assertContains(
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
