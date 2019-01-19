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

	protected function tearDown() {
		Localizer::clear();
	}

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

		$this->assertTrue(
			Localizer::isKnownLanguageTag( 'en' )
		);
	}

	public function testSupportedLanguageForUpperCaseLetter() {

		$this->assertTrue(
			Localizer::isKnownLanguageTag( 'ZH-HANS' )
		);
	}

	public function testAsBCP47FormattedLanguageCode() {
		$this->assertEquals(
			'zh-Hans',
			Localizer::asBCP47FormattedLanguageCode( 'zh-hans' )
		);
	}

	public function testCanGetAnnotatedLanguageCodeOnValidMarkedValue() {

		$value = 'Foo@en';

		$this->assertEquals(
			'en',
			Localizer::getAnnotatedLanguageCodeFrom( $value )
		);

		$this->assertEquals(
			'Foo',
			$value
		);
	}

	public function testCanGetAnnotatedLanguageCodeOnDoubledMarkedValue() {

		$value = 'Foo@@en';

		$this->assertEquals(
			'en',
			Localizer::getAnnotatedLanguageCodeFrom( $value )
		);

		$this->assertEquals(
			'Foo@',
			$value
		);
	}

	public function testCanGetAnnotatedLanguageCodeOnValueWithDash() {

		$value = 'Foo@zh-Hans';

		$this->assertEquals(
			'zh-Hans',
			Localizer::getAnnotatedLanguageCodeFrom( $value )
		);

		$this->assertEquals(
			'Foo',
			$value
		);
	}

	public function testCanNotGetAnnotatedLanguageCodeThatContainsInvalidCharacter() {

		$value = 'Foo@en#bar';

		$this->assertFalse(
			Localizer::getAnnotatedLanguageCodeFrom( $value )
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

	public function testGetLanguageCodeByRule_OnTitleExpectedToPageLanguage() {

		$contentLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Localizer( $contentLanguage );

		$pageLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $pageLanguage ) );

		$this->assertEquals(
			$pageLanguage,
			$instance->getPreferredContentLanguage( $title )
		);
	}

	public function testGetLanguageCodeByRule_OnNotProvidedTitlePageLanguageExpectedToReturnUserLanguage() {

		$contentLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Localizer( $contentLanguage );

		$this->assertEquals(
			$instance->getContentLanguage(),
			$instance->getPreferredContentLanguage( null )
		);
	}

	public function testLang() {

		$instance = Localizer::getInstance();

		$this->assertInstanceOf(
			'\SMW\Lang\Lang',
			$instance->getLang()
		);

		$this->assertInstanceOf(
			'\SMW\Lang\Lang',
			$instance->getLang( 'en' )
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'getCode' )
			->will( $this->returnValue( 'en' ) );

		$this->assertInstanceOf(
			'\SMW\Lang\Lang',
			$instance->getLang( $language )
		);
	}

	public function testConvertDoubleWidth() {

		$this->assertEquals(
			'2000',
			Localizer::convertDoubleWidth( '２０００' )
		);

		$this->assertEquals(
			'aBc',
			Localizer::getInstance()->convertDoubleWidth( 'ａＢｃ' )
		);
	}

	public function testCreateTextWithNamespacePrefix() {

		$instance = new Localizer( Language::factory( 'en') );

		$this->assertEquals(
			'Property:foo bar',
			$instance->createTextWithNamespacePrefix( SMW_NS_PROPERTY, 'foo bar' )
		);
	}

	public function testGetCanonicalizedUrlByNamespace() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->exactly( 3 ) )
			->method( 'getNsText' )
			->will( $this->returnValue( 'Spécial' ) );

		$instance = new Localizer( $language );

		$this->assertEquals(
			'http://example.org/wiki/Special:URIResolver/Property-3AHas_query',
			$instance->getCanonicalizedUrlByNamespace( NS_SPECIAL, 'http://example.org/wiki/Sp%C3%A9cial:URIResolver/Property-3AHas_query' )
		);

		$this->assertEquals(
			'http://example.org/wiki/Special:URIResolver/Property-3AHas_query',
			$instance->getCanonicalizedUrlByNamespace( NS_SPECIAL, 'http://example.org/wiki/Spécial:URIResolver/Property-3AHas_query' )
		);

		$this->assertEquals(
			'http://example.org/index.php?title=Special:URIResolver&Property-3AHas_query',
			$instance->getCanonicalizedUrlByNamespace( NS_SPECIAL, 'http://example.org/index.php?title=Spécial:URIResolver&Property-3AHas_query' )
		);
	}

	public function testGetCanonicalName() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Localizer( $language );

		$this->assertEquals(
			'Property',
			$instance->getCanonicalNamespaceTextById( SMW_NS_PROPERTY )
		);

		$this->assertEquals(
			'Help',
			$instance->getCanonicalNamespaceTextById( NS_HELP )
		);
	}

	public function testHasLocalTimeOffsetPreference() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'getOption' )
			->with( $this->equalTo( 'smw-prefs-general-options-time-correction' ) )
			->will( $this->returnValue( true ) );

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Localizer( $language );

		$this->assertTrue(
			$instance->hasLocalTimeOffsetPreference( $user )
		);
	}

	public function testGetLocalTime() {

		$dataTime = $this->getMockBuilder( '\DateTime' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Localizer( $language );

		$this->assertInstanceOf(
			'DateTime',
			$instance->getLocalTime( $dataTime, $user )
		);
	}

}
