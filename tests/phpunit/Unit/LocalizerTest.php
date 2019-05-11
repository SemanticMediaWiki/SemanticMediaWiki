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

	private $language;
	private $namespaceInfo;

	protected function setUp() {
		parent::setUp();

		$this->language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceInfo = $this->getMockBuilder( '\SMW\MediaWiki\NamespaceInfo' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		Localizer::clear();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Localizer::class,
			new Localizer( $this->language, $this->namespaceInfo )
		);

		$this->assertInstanceOf(
			Localizer::class,
			Localizer::getInstance()
		);
	}

	public function testGetContentLanguage() {

		$instance = new Localizer(
			$this->language,
			$this->namespaceInfo
		);

		$this->assertSame(
			$this->language,
			$instance->getContentLanguage()
		);

		$this->assertSame(
			$GLOBALS['wgContLang'],
			Localizer::getInstance()->getContentLanguage()
		);
	}

	public function testNamespaceTextById() {

		$instance = new Localizer(
			Language::factory( 'en' ),
			$this->namespaceInfo
		);

		$this->assertEquals(
			'Property',
			$instance->getNamespaceTextById( SMW_NS_PROPERTY )
		);
	}

	public function testNamespaceIndexByName() {

		$instance = new Localizer(
			Language::factory( 'en'),
			$this->namespaceInfo
		);

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

		$instance = new Localizer(
			$this->language,
			$this->namespaceInfo
		);

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

		$instance = new Localizer(
			$this->language,
			$this->namespaceInfo
		);

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

		$instance = new Localizer(
			Language::factory( 'en'),
			$this->namespaceInfo
		);

		$this->assertEquals(
			'Property:foo bar',
			$instance->createTextWithNamespacePrefix( SMW_NS_PROPERTY, 'foo bar' )
		);
	}

	public function testGetCanonicalizedUrlByNamespace() {

		$this->language->expects( $this->exactly( 3 ) )
			->method( 'getNsText' )
			->will( $this->returnValue( 'Spécial' ) );

		$this->namespaceInfo->expects( $this->exactly( 3 ) )
			->method( 'getCanonicalName' )
			->will( $this->returnValue( 'Special' ) );

		$instance = new Localizer(
			$this->language,
			$this->namespaceInfo
		);

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

		$this->namespaceInfo->expects( $this->once() )
			->method( 'getCanonicalName' )
			->will( $this->returnValue( 'Help' ) );

		$instance = new Localizer(
			$this->language,
			$this->namespaceInfo
		);

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

		$instance = new Localizer(
			$this->language,
			$this->namespaceInfo
		);

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

		$instance = new Localizer(
			$this->language,
			$this->namespaceInfo
		);

		$this->assertInstanceOf(
			'DateTime',
			$instance->getLocalTime( $dataTime, $user )
		);
	}

}
