<?php

namespace SMW\Tests\Integration\ExtraneousLanguage;

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
class LanguageContent extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		ExtraneousLanguage::clear();
		parent::tearDown();
	}

	/**
	 * @dataProvider canonicalPropertyAliasesProvider
	 */
	public function testGetCanonicalPropertyAliases( $languageCode, $canonicalMatch, $aliasMatch, $expected ) {

		$extraneousLanguage = ExtraneousLanguage::getInstance()->fetchByLanguageCode(
			$languageCode
		);

		$canonicalPropertyAliases = $extraneousLanguage->getCanonicalPropertyAliases();

		$this->assertEquals(
			$expected,
			$canonicalPropertyAliases[$canonicalMatch]
		);
	}

	/**
	 * @dataProvider canonicalPropertyAliasesProvider
	 */
	public function testGetPropertyAliases( $languageCode, $canonicalMatch, $aliasMatch, $expected ) {

		$extraneousLanguage = ExtraneousLanguage::getInstance()->fetchByLanguageCode(
			$languageCode
		);

		$propertyAliases = $extraneousLanguage->getPropertyAliases();

		$this->assertEquals(
			$expected,
			$propertyAliases[$aliasMatch]
		);
	}

	/**
	 * @dataProvider canonicalPropertyLabelsProvider
	 */
	public function testGetCanonicalPropertyLabels( $languageCode, $aliasMatch, $expected ) {

		$extraneousLanguage = ExtraneousLanguage::getInstance()->fetchByLanguageCode(
			$languageCode
		);

		$propertyLabels = $extraneousLanguage->getCanonicalPropertyLabels();

		$this->assertEquals(
			$expected,
			$propertyLabels[$aliasMatch]
		);
	}

	public function canonicalPropertyAliasesProvider() {

		$provider[] = array(
			'fr',
			'Query size',
			'Taille de la requête',
			'_ASKSI'
		);

		return $provider;
	}

	public function canonicalPropertyLabelsProvider() {

		$provider[] = array(
			'fr',
			'Boolean',
			'_boo'
		);

		$provider[] = array(
			'en',
			'Float',
			'_num'
		);

		return $provider;
	}

}
