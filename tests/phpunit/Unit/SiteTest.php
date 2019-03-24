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

	public function testIsBlocked() {

		$this->assertInternalType(
			'boolean',
			Site::isBlocked()
		);
	}

	public function testName() {

		$this->assertInternalType(
			'string',
			Site::name()
		);
	}

	public function testWikiurl() {

		$this->assertInternalType(
			'string',
			Site::wikiurl()
		);
	}

	public function testLanguageCode() {

		$this->assertInternalType(
			'string',
			Site::languageCode()
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

	public function testGetCacheExpireTime() {

		$this->assertInternalType(
			'integer',
			Site::getCacheExpireTime( 'parser' )
		);
	}

	public function testStats() {

		$this->assertInternalType(
			'array',
			Site::stats()
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
