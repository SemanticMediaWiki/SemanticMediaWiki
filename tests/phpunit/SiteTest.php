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
		$this->assertIsBool(

			Site::isReadOnly()
		);
	}

	public function testIsReady() {
		$this->assertIsBool(

			Site::isReady()
		);
	}

	public function testName() {
		$this->assertIsString(

			Site::name()
		);
	}

	public function testWikiurl() {
		$this->assertIsString(

			Site::wikiurl()
		);
	}

	public function testLanguageCode() {
		$this->assertIsString(

			Site::languageCode()
		);
	}

	public function testIsCommandLineMode() {
		$this->assertIsBool(

			Site::isCommandLineMode()
		);
	}

	public function testIsCapitalLinks() {
		$this->assertIsBool(

			Site::isCapitalLinks()
		);
	}

	public function testGetCacheExpireTime() {
		$this->assertIsInt(

			Site::getCacheExpireTime( 'parser' )
		);
	}

	public function testStats() {
		$this->assertIsArray(

			Site::stats()
		);
	}

	public function testGetJobClasses() {
		$this->assertIsArray(

			Site::getJobClasses()
		);

		$this->assertNotEmpty(
			Site::getJobClasses( 'SMW' )
		);
	}

}
