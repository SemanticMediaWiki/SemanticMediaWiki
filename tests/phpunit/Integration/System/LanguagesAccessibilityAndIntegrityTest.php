<?php

namespace SMW\Tests\System;

use RuntimeException;
use SMW\ExtraneousLanguage\ExtraneousLanguage;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-system
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class LanguagesAccessibilityAndIntegrityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testCommonInterfaceMethods( $langcode ) {

		$methods = array(
			'getDateFormats' => 'array',
			'getNamespaces'  => 'array',
			'getNamespaceAliases' => 'array',
			'getDatatypeLabels'   => 'array',
			'getDatatypeAliases'  => 'array',
			'getPropertyLabels'   => 'array',
			'getPropertyAliases'  => 'array',
		);

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		foreach ( $methods as $method => $type ) {
			$this->assertInternalType( $type, call_user_func( array( $class, $method ) ) );
		}
	}

	/**
	 * @depends testCommonInterfaceMethods
	 * @dataProvider languageCodeProvider
	 */
	public function testComparePredefinedPropertyLabels( $langcode ) {

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		$baseToCompareInstance = ExtraneousLanguage::getInstance()->fetchByLanguageCode( 'en' );
		$targetLanguageInstance = $class;

		$result = array_diff_key(
			$baseToCompareInstance->getPropertyLabels(),
			$targetLanguageInstance->getPropertyLabels()
		);

		$this->assertTrue(
			$result === array(),
			"Asserts predfined property keys for the language pair EN - {$langcode} with {$this->formatAsString($result)}"
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testCompareMonthAndLabel( $langcode ) {

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		for ( $i=1; $i <= 12; $i++ ) {

			$label = call_user_func( array( $class, 'getMonthLabel' ), $i );
			$month = call_user_func( array( $class, 'findMonth' ), $label );

			$this->assertInternalType( 'string', $label );
			$this->assertEquals( $i, $month );
		}
	}

	public function languageCodeProvider() {

		$provider = array();

		$languageCodes = array(
			'En', 'Ar', 'Arz', 'Ca', 'De', 'Es', 'Fi',
			'Fr', 'He', 'Id', 'It', 'Nb', 'Nl', 'Pl',
			'Pt', 'Ru', 'Sk', 'Zh-cn', 'Zh-tw'
		);

		foreach ( $languageCodes as $code ) {
			$provider[] = array( $code );
		}

		return $provider;
	}

	private function loadLanguageFileAndConstructClass( $langcode ) {
		return ExtraneousLanguage::getInstance()->fetchByLanguageCode( $langcode );
	}

	private function formatAsString( $expected ) {
		return is_array( $expected ) ? implode( ', ', $expected ) : $expected;
	}

}
