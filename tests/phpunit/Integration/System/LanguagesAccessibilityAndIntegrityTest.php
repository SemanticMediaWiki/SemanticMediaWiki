<?php

namespace SMW\Tests\System;

use SMW\Tests\Utils\GlobalsProvider;
use RuntimeException;

/**
 * @covers \SMWLanguage
 *
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

	private $globalsProvider;

	protected function setUp() {
		parent::setUp();

		$this->globalsProvider = GlobalsProvider::getInstance();
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testLanguageIsAvailable( $langcode ) {
		$this->assertTrue( class_exists( $this->loadLanguageFileAndConstructClass( $langcode ) ) );
	}

	/**
	 * @depends testLanguageIsAvailable
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
			$this->assertInternalType( $type, call_user_func( array( new $class, $method ) ) );
		}
	}

	/**
	 * @depends testCommonInterfaceMethods
	 * @dataProvider languageCodeProvider
	 */
	public function testComparePredefinedPropertyLabels( $langcode ) {

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		$baseToCompareInstance = new \SMWLanguageEn;
		$targetLanguageInstance = new $class;

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

			$label = call_user_func( array( new $class, 'getMonthLabel' ), $i );
			$month = call_user_func( array( new $class, 'findMonth' ), $label );

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

	/**
	 * @note Language files are not resolved by the Composer classmap
	 */
	private function loadLanguageFileAndConstructClass( $langcode ) {

		$lang = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
		$file = $this->globalsProvider->get( 'smwgIP' ) . '/' . 'languages' . '/' . $lang . '.php';

		if ( file_exists( $file ) ) {
			include_once ( $file );
			return 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );
		}

		throw new RuntimeException( "Expected {$file} to be accessible" );
	}

	private function formatAsString( $expected ) {
		return is_array( $expected ) ? implode( ', ', $expected ) : $expected;
	}

}
