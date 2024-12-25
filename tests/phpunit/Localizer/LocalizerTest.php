<?php

namespace SMW\Tests\Localizer;

use DateTime;
use IContextSource;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserOptionsLookup;
use SMW\Localizer\Localizer;

/**
 * @covers \SMW\Localizer\Localizer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class LocalizerTest extends \PHPUnit\Framework\TestCase {

	private $language;
	private $namespaceInfo;

	private UserOptionsLookup $userOptionsLookup;

	private IContextSource $context;

	protected function setUp(): void {
		parent::setUp();

		$this->language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceInfo = $this->getMockBuilder( '\SMW\MediaWiki\NamespaceInfo' )
			->disableOriginalConstructor()
			->getMock();

		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->context = $this->createMock( IContextSource::class );
	}

	protected function tearDown(): void {
		Localizer::clear();
	}

	private function newLocalizer(): Localizer {
		return new Localizer(
			$this->language,
			$this->namespaceInfo,
			$this->userOptionsLookup,
			$this->context
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Localizer::class,
			$this->newLocalizer()
		);

		$this->assertInstanceOf(
			Localizer::class,
			Localizer::getInstance()
		);
	}

	public function testGetContentLanguage() {
		$instance = $this->newLocalizer();

		$this->assertSame(
			$this->language,
			$instance->getContentLanguage()
		);
	}

	public function testNamespaceTextById() {
		$this->language->expects( $this->any() )
			->method( 'getNsText' )
			->with( SMW_NS_PROPERTY )
			->willReturn( 'Property' );

		$instance = $this->newLocalizer();
		$this->assertEquals(
			'Property',
			$instance->getNsText( SMW_NS_PROPERTY )
		);
	}

	public function testNamespaceIndexByName() {
		$this->language->expects( $this->any() )
			->method( 'getNsIndex' )
			->with( 'property' )
			->willReturn( SMW_NS_PROPERTY );

		$instance = $this->newLocalizer();

		$this->assertEquals(
			SMW_NS_PROPERTY,
			$instance->getNsIndex( 'property' )
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
		$instance = $this->newLocalizer();

		$pageLanguage = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPageLanguage' )
			->willReturn( $pageLanguage );

		$this->assertEquals(
			$pageLanguage,
			$instance->getPreferredContentLanguage( $title )
		);
	}

	public function testGetLanguageCodeByRule_OnNotProvidedTitlePageLanguageExpectedToReturnUserLanguage() {
		$instance = $this->newLocalizer();

		$this->assertEquals(
			$instance->getContentLanguage(),
			$instance->getPreferredContentLanguage( null )
		);
	}

	public function testLang() {
		$instance = Localizer::getInstance();

		$this->assertInstanceOf(
			'\SMW\Localizer\LocalLanguage\LocalLanguage',
			$instance->getLang()
		);

		$this->assertInstanceOf(
			'\SMW\Localizer\LocalLanguage\LocalLanguage',
			$instance->getLang( 'en' )
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'getCode' )
			->willReturn( 'en' );

		$this->assertInstanceOf(
			'\SMW\Localizer\LocalLanguage\LocalLanguage',
			$instance->getLang( $language )
		);
	}

	public function testConvertDoubleWidth() {
		$this->assertSame(
			'2000',
			Localizer::convertDoubleWidth( '２０００' )
		);

		$this->assertEquals(
			'aBc',
			Localizer::getInstance()->convertDoubleWidth( 'ａＢｃ' )
		);
	}

	public function testCreateTextWithNamespacePrefix() {
		$this->language->expects( $this->any() )
			->method( 'getNsText' )
			->with( SMW_NS_PROPERTY )
			->willReturn( 'Property' );

		$instance = $this->newLocalizer();

		$this->assertEquals(
			'Property:foo bar',
			$instance->createTextWithNamespacePrefix( SMW_NS_PROPERTY, 'foo bar' )
		);
	}

	public function testNormalizeTitleText() {
		$this->language->expects( $this->once() )
			->method( 'ucfirst' )
			->willReturn( 'Fo_o' );

		$instance = $this->newLocalizer();

		$this->assertEquals(
			'Fo o',
			$instance->normalizeTitleText( 'fo_o' )
		);
	}

	public function testGetCanonicalizedUrlByNamespace() {
		$this->language->expects( $this->exactly( 3 ) )
			->method( 'getNsText' )
			->willReturn( 'Spécial' );

		$this->namespaceInfo->expects( $this->exactly( 3 ) )
			->method( 'getCanonicalName' )
			->willReturn( 'Special' );

		$instance = $this->newLocalizer();

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
			->willReturn( 'Help' );

		$instance = $this->newLocalizer();

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

		$this->userOptionsLookup->expects( $this->once() )
			->method( 'getOption' )
			->with( $user, 'smw-prefs-general-options-time-correction' )
			->willReturn( true );

		$instance = $this->newLocalizer();

		$this->assertTrue(
			$instance->hasLocalTimeOffsetPreference( $user )
		);
	}

	public function testGetLocalTime() {
		$dataTime = new DateTime();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->newLocalizer();

		$this->assertInstanceOf(
			'DateTime',
			$instance->getLocalTime( $dataTime, $user )
		);
	}

}
