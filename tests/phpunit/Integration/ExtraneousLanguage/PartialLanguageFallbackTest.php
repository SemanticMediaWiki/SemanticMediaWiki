<?php

namespace SMW\Tests\Integration\ExtraneousLanguage;

use SMW\ExtraneousLanguage\LanguageFallbackFinder;
use SMW\ExtraneousLanguage\LanguageFileContentsReader;
use SMW\ExtraneousLanguage\LanguageContents;
use SMW\ExtraneousLanguage\ExtraneousLanguage;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PartialLanguageFallback extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
		LanguageFileContentsReader::clear();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		LanguageFileContentsReader::clear();
		parent::tearDown();
	}

	public function testDeclarationsLoadedPartiallyFromFallback() {

		$languageFileContentsReader = new LanguageFileContentsReader(
			null,
			$this->testEnvironment->getFixturesLocation( 'ExtraneousLanguage' )
		);

		$languageContents = new LanguageContents(
			$languageFileContentsReader,
			new LanguageFallbackFinder( $languageFileContentsReader )
		);

		$extraneousLanguage = new ExtraneousLanguage(
			$languageContents
		);

		$extraneousLanguage = $extraneousLanguage->fetchByLanguageCode( 'foo-partial' );

		// Loaded from foo-partial.json
		$this->assertEquals(
			array(
				'dataTypeLabels-partial' => 'bar'
			),
			$extraneousLanguage->getDatatypeLabels()
		);

		// foo-partial.json doesn't contain a `dataTypeAliases` declaration and is
		// only available in its fallback (foo-fallback.json)
		$this->assertEquals(
			array(
				'dataTypeAliases-fallback' => 'bar'
			),
			$extraneousLanguage->getDatatypeAliases()
		);
	}

}
