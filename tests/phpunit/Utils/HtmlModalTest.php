<?php

namespace SMW\Tests\Utils;

use SMW\Tests\PHPUnitCompat;
use SMW\Utils\HtmlModal;

/**
 * @covers \SMW\Utils\HtmlModal
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlModalTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testGetModules() {
		$this->assertIsArray(

			HtmlModal::getModules()
		);

		$this->assertIsArray(

			HtmlModal::getModuleStyles()
		);
	}

	public function testLink() {
		$this->assertContains(
			'smw-modal-link',
			HtmlModal::link( 'Foo' )
		);
	}

	public function testElement() {
		$this->assertContains(
			'smw-modal-link',
			HtmlModal::element( 'Foo' )
		);
	}

	public function testModal() {
		$this->assertContains(
			'smw-modal',
			HtmlModal::modal( 'Foo' )
		);
	}

}
