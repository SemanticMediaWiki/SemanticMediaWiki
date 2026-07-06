<?php

namespace SMW\Tests\Unit\Localizer\LocalLanguage;

use PHPUnit\Framework\TestCase;
use SMW\Localizer\LocalLanguage\LocalLanguage;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageContent extends TestCase {

	protected function tearDown(): void {
		LocalLanguage::clear();
		parent::tearDown();
	}

	/**
	 * @dataProvider canonicalPropertyAliasesProvider
	 */
	public function testGetCanonicalPropertyAliases( $languageCode, $canonicalMatch, $aliasMatch, $expected ) {
		$lang = LocalLanguage::getInstance()->fetch(
			$languageCode
		);

		$canonicalPropertyAliases = $lang->getCanonicalPropertyAliases();

		$this->assertEquals(
			$expected,
			$canonicalPropertyAliases[$canonicalMatch]
		);
	}

	/**
	 * @dataProvider canonicalPropertyAliasesProvider
	 */
	public function testGetPropertyAliases( $languageCode, $canonicalMatch, $aliasMatch, $expected ) {
		$lang = LocalLanguage::getInstance()->fetch(
			$languageCode
		);

		$propertyAliases = $lang->getPropertyAliases();

		$this->assertEquals(
			$expected,
			$propertyAliases[$aliasMatch]
		);
	}

	/**
	 * @dataProvider canonicalPropertyLabelsProvider
	 */
	public function testGetCanonicalPropertyLabels( $languageCode, $aliasMatch, $expected ) {
		$lang = LocalLanguage::getInstance()->fetch(
			$languageCode
		);

		$propertyLabels = $lang->getCanonicalPropertyLabels();

		$this->assertEquals(
			$expected,
			$propertyLabels[$aliasMatch]
		);
	}

	public function canonicalPropertyAliasesProvider() {
		$provider[] = [
			'fr',
			'Query size',
			'Taille de la requête',
			'_ASKSI'
		];

		return $provider;
	}

	public function canonicalPropertyLabelsProvider() {
		$provider[] = [
			'fr',
			'Boolean',
			'_boo'
		];

		$provider[] = [
			'en',
			'Float',
			'_num'
		];

		return $provider;
	}

}
