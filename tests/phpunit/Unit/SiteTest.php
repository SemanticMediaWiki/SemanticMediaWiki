<?php

namespace SMW\Tests;

use SMW\Site;

/**
 * @covers \SMW\Site
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class SiteTest extends \PHPUnit_Framework_TestCase {

	public function testIsReadOnly() {

		$this->assertInternalType(
			'boolean',
			Site::isReadOnly()
		);
	}

	public function testIsCommandLineMode() {

		$this->assertInternalType(
			'boolean',
			Site::isCommandLineMode()
		);
	}

	public function testIsCapitalLinks() {

		$this->assertInternalType(
			'boolean',
			Site::isCapitalLinks()
		);
	}

	public function testGetJobClasses() {

		$this->assertInternalType(
			'array',
			Site::getJobClasses()
		);

		$this->assertNotEmpty(
			Site::getJobClasses( 'SMW' )
		);
	}

}
