<?php

namespace SMW\Tests\Utils;

use SMW\Utils\HtmlModal;

/**
 * @covers \SMW\Utils\HtmlModal
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlModalTest extends \PHPUnit_Framework_TestCase {

	public function testGetModules() {

		$this->assertInternalType(
			'array',
			HtmlModal::getModules()
		);

		$this->assertInternalType(
			'array',
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
