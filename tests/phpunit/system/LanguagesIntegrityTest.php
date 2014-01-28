<?php

namespace SMW\Test;

use SMW\Configuration\Configuration;

/**
 * @covers \SMWLanguage
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class LanguagesIntegrityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testLanguageIsAvailable( $langcode ) {
		$this->assertTrue( class_exists( $this->loadLanguageFileAndConstructClass( $langcode ) ) );
	}

	/**
	 * @dataProvider languageCodeProvider
	 * @depends testLanguageIsAvailable
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
	 * @dataProvider languageCodeProvider
	 * @depends testCommonInterfaceMethods
	 */
	public function testComparePredefinedPropertyLabels( $langcode ) {

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		$baseToCompareInstance = new \SMWLanguageEn;
		$targetLanguageInstance = new $class;

		$result = array_diff_key(
			$baseToCompareInstance->getPropertyLabels(),
			$targetLanguageInstance->getPropertyLabels()
		);

		if ( $result !== array() ) {
			$this->markTestIncomplete(
				'Test marked as incomplete for language ' . $langcode . ' due to ' . implode( ', ' , $result )
			);
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testCompareMonthAndLabel( $langcode ) {

		$class = $this->loadLanguageFileAndConstructClass( $langcode );

		for ( $i=1; $i <= 12; $i++ ) {

			$label = call_user_func_array( array( new $class, 'getMonthLabel' ), array( $i ) );
			$month = call_user_func_array( array( new $class, 'findMonth' ), array( $label ) );

			$this->assertInternalType( 'string', $label );
			$this->assertEquals( $i, $month );
		}

	}

	/**
	 * @note Language files are not resolved by the Composer classmap
	 */
	protected function loadLanguageFileAndConstructClass( $langcode ) {

		$lang = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
		$file = Configuration::getInstance()->get( 'smwgIP' ) . '/' . 'languages' . '/' . $lang . '.php';

		if ( file_exists( $file ) ) {
			include_once( $file );
		}

		return 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );
	}

	public function languageCodeProvider() {

		$provider = array();

		$languageCodes = array(
			'En', 'Ar', 'Arz', 'Ca', 'De', 'Es', 'Fi',
			'Fr', 'He', 'Id', 'It', 'Nl', 'No', 'Pl',
			'Pt', 'Ru', 'Sk', 'Zh-cn', 'Zh-tw'
		);

		foreach ( $languageCodes as $code ) {
			$provider[] = array( $code );
		}

		return $provider;
	}

}
