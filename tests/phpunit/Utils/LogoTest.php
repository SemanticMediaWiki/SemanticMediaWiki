<?php

namespace SMW\Tests\Utils;

use SMW\Utils\Logo;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\Logo
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
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
