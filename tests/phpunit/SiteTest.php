<?php

namespace SMW\Tests;

use SMW\Site;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Site
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class SiteTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	protected function setUp(): void {
		parent::setUp();

		// Mocking global job classes
		$GLOBALS['wgJobClasses'] = [
			'smw.indexer' => 'SMWIndexerJob',
			'smw.updater' => 'SMWUpdaterJob',
			// Add more mock job classes as necessary for your tests
		];
	}

	public function testIsReadOnly() {
		$this->assertInternalType(
			'boolean',
			Site::isReadOnly()
		);
	}

	public function testIsReady() {
		$this->assertInternalType(
			'boolean',
			Site::isReady()
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
