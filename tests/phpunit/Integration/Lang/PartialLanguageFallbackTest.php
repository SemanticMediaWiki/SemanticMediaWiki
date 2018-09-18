<?php

namespace SMW\Tests\Integration\Lang;

use SMW\Lang\FallbackFinder;
use SMW\Lang\JsonContentsFileReader;
use SMW\Lang\Lang;
use SMW\Lang\LanguageContents;
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
		JsonContentsFileReader::clear();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		JsonContentsFileReader::clear();

		parent::tearDown();
	}

	public function testDeclarationsLoadedPartiallyFromFallback() {

		$JsonContentsFileReader = new JsonContentsFileReader(
			null,
			__DIR__ . '/Fixtures'
		);

		$languageContents = new LanguageContents(
			$JsonContentsFileReader,
			new FallbackFinder( $JsonContentsFileReader )
		);

		$lang = new Lang(
			$languageContents
		);

		$lang = $lang->fetch( 'foo-partial' );

		// Loaded from foo-partial.json
		$this->assertEquals(
			[
				'dataTypeLabels-partial' => 'bar'
			],
			$lang->getDatatypeLabels()
		);

		// foo-partial.json doesn't contain a `dataTypeAliases` declaration and is
		// only available in its fallback (foo-fallback.json)
		$this->assertEquals(
			[
				'dataTypeAliases-fallback' => 'bar'
			],
			$lang->getDatatypeAliases()
		);
	}

}
