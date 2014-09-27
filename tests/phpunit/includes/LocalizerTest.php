<?php

namespace SMW\Tests;

use SMW\Localizer;

use Language;

/**
 * @covers \SMW\Localizer
 *
 * @group SMW
 * @group SMWExtension
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

}
