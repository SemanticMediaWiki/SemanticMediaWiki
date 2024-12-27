<?php

namespace SMW\Tests\Structure;

use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki-system
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class LocalLanguageAccessibilityAndIntegrityTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testCommonInterfaceMethods( $langcode ) {
		$methods = [
			'getDateFormats' => 'array',
			'getNamespaces'  => 'array',
			'getNamespaceAliases' => 'array',
			'getDatatypeLabels'   => 'array',
			'getDatatypeAliases'  => 'array',
			'getPropertyLabels'   => 'array',
			'getPropertyAliases'  => 'array',
		];

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		foreach ( $methods as $method => $type ) {
			$this->assertInternalType( $type, call_user_func( [ $class, $method ] ) );
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
