<?php

namespace SMW\Tests\Integration\Lang;

use SMW\Lang\Lang;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageContent extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		Lang::clear();
		parent::tearDown();
	}

	/**
	 * @dataProvider canonicalPropertyAliasesProvider
	 */
	public function testGetCanonicalPropertyAliases( $languageCode, $canonicalMatch, $aliasMatch, $expected ) {

		$lang = Lang::getInstance()->fetch(
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

		$lang = Lang::getInstance()->fetch(
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

		$lang = Lang::getInstance()->fetch(
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
