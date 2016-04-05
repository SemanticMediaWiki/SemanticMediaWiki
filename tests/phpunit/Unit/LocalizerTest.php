<?php

namespace SMW\Tests;

use Language;
use SMW\Localizer;

/**
 * @covers \SMW\Localizer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class LocalizerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Localizer',
			new Localizer( $language )
		);

		$this->assertInstanceOf(
			'\SMW\Localizer',
			Localizer::getInstance()
		);
	}

	public function testGetContentLanguage() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Localizer( $language );

		$this->assertSame(
			$language,
			$instance->getContentLanguage()
		);

		$this->assertSame(
			$GLOBALS['wgContLang'],
			Localizer::getInstance()->getContentLanguage()
		);

		Localizer::clear();
	}

	public function testNamespaceTextById() {

		$instance = new Localizer( Language::factory( 'en') );

		$this->assertEquals(
			'Property',
			$instance->getNamespaceTextById( SMW_NS_PROPERTY )
		);
	}

	public function testNamespaceIndexByName() {

		$instance = new Localizer( Language::factory( 'en') );

		$this->assertEquals(
			SMW_NS_PROPERTY,
			$instance->getNamespaceIndexByName( 'property' )
		);
	}

	public function testSupportedLanguageForLowerCaseLetter() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( 'Skipping because `Language::isSupportedLanguage` is not supported on 1.19' );
		}

		$this->assertTrue(
			Localizer::isSupportedLanguage( 'en' )
		);
	}

	public function testSupportedLanguageForUpperCaseLetter() {

		if ( version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ) {
			$this->markTestSkipped( 'Skipping because `Language::isSupportedLanguage` is not supported on 1.19' );
		}

		$this->assertTrue(
			Localizer::isSupportedLanguage( 'ZH-HANS' )
		);
	}

	public function testAsBCP47FormattedLanguageCode() {
		$this->assertEquals(
			'zh-Hans',
			Localizer::asBCP47FormattedLanguageCode( 'zh-hans' )
		);
	}

	public function testCanGetLanguageCodeOnValidMarkedValue() {

		$value = 'Foo@en';

		$this->assertEquals(
			'en',
			Localizer::getLanguageCodeFrom( $value )
		);

		$this->assertEquals(
			'Foo',
			$value
		);
	}

	public function testCanGetLanguageCodeOnDoubledMarker() {

		$value = 'Foo@@en';

		$this->assertEquals(
			'en',
			Localizer::getLanguageCodeFrom( $value )
		);

		$this->assertEquals(
			'Foo@',
			$value
		);
	}

	public function testCanNotGetLanguageCodeOnNonMarkedValue() {

		$value = 'Fooen';

		$this->assertFalse(
			Localizer::getLanguageCodeFrom( $value )
		);

		$this->assertEquals(
			'Fooen',
			$value
		);
	}

	public function testCanNotGetLanguageCodeOnMissingLanguageCode() {

		$value = 'Foo@';

		$this->assertFalse(
			Localizer::getLanguageCodeFrom( $value )
		);

		$this->assertEquals(
			'Foo@',
			$value
		);
	}

}
