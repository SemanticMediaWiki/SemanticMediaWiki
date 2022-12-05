<?php

namespace SMW\Tests\Integration\Localizer\LocalLanguage;

use SMW\Localizer\LocalLanguage\FallbackFinder;
use SMW\Localizer\LocalLanguage\JsonContentsFileReader;
use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\Localizer\LocalLanguage\LanguageContents;
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

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		JsonContentsFileReader::clear();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		JsonContentsFileReader::clear();

		parent::tearDown();
	}

	public function testDeclarationsLoadedPartiallyFromFallback() {

		$JsonContentsFileReader = new JsonContentsFileReader(
			null,
			SMW_PHPUNIT_DIR . '/Fixtures/Localizer/LocalLanguage/'
		);

		$languageContents = new LanguageContents(
			$JsonContentsFileReader,
			new FallbackFinder( $JsonContentsFileReader )
		);

		$localLanguage = new LocalLanguage(
			$languageContents
		);

		$localLanguage = $localLanguage->fetch( 'foo-partial' );

		// Loaded from foo-partial.json
		$this->assertEquals(
			[
				'dataTypeLabels-partial' => 'bar'
			],
			$localLanguage->getDatatypeLabels()
		);

		// foo-partial.json doesn't contain a `dataTypeAliases` declaration and is
		// only available in its fallback (foo-fallback.json)
		$this->assertEquals(
			[
				'dataTypeAliases-fallback' => 'bar'
			],
			$localLanguage->getDatatypeAliases()
		);
	}

}
