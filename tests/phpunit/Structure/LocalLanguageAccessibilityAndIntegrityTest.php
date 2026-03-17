<?php

namespace SMW\Tests\Structure;

use PHPUnit\Framework\TestCase;
use SMW\Localizer\LocalLanguage\LocalLanguage;

/**
 * @group semantic-mediawiki-system
 *
 * @license GPL-2.0-or-later
 * @since 1.9.1
 *
 * @author mwjames
 */
class LocalLanguageAccessibilityAndIntegrityTest extends TestCase {

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testCommonInterfaceMethods( $langcode ) {
		$methods = [
			'getDateFormats',
			'getNamespaces',
			'getNamespaceAliases',
			'getDatatypeLabels',
			'getDatatypeAliases',
			'getPropertyLabels',
			'getPropertyAliases',
		];

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		foreach ( $methods as $method ) {
			$this->assertIsArray( call_user_func( [ $class, $method ] ) );
		}
	}

	/**
	 * @depends testCommonInterfaceMethods
	 * @dataProvider languageCodeProvider
	 */
	public function testComparePredefinedPropertyLabels( $langcode ) {
		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		$baseToCompareInstance = LocalLanguage::getInstance()->fetch( 'en' );
		$targetLanguageInstance = $class;

		$result = array_diff_key(
			$baseToCompareInstance->getPropertyLabels(),
			$targetLanguageInstance->getPropertyLabels()
		);

		$this->assertTrue(
			$result === [],
			"Asserts predfined property keys for the language pair EN - {$langcode} with {$this->formatAsString($result)}"
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testCompareMonthAndLabel( $langcode ) {
		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		for ( $i = 1; $i <= 12; $i++ ) {

			$label = call_user_func( [ $class, 'getMonthLabel' ], $i );
			$month = call_user_func( [ $class, 'findMonth' ], $label );

			$this->assertIsString( $label );
			$this->assertEquals( $i, $month );
		}
	}

	public function languageCodeProvider() {
		$provider = [];

		$languageCodes = [
			'En', 'Ar', 'Arz', 'Ca', 'De', 'Es', 'Fi',
			'Fr', 'He', 'Id', 'It', 'Nb', 'Nl', 'Pl',
			'Pt', 'Ru', 'Sk', 'Zh-cn', 'Zh-tw'
		];

		foreach ( $languageCodes as $code ) {
			$provider[] = [ $code ];
		}

		return $provider;
	}

	private function loadLanguageFileAndConstructClass( $langcode ) {
		return LocalLanguage::getInstance()->fetch( $langcode );
	}

	private function formatAsString( $expected ) {
		return is_array( $expected ) ? implode( ', ', $expected ) : $expected;
	}

}
