<?php

namespace SMW\Tests;

use SMW\ExtraneousLanguage;

/**
 * @covers \SMW\ExtraneousLanguage
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ExtraneousLanguageTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$extraneousLanguageFileHandler = $this->getMockBuilder( '\SMW\ExtraneousLanguageFileHandler' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ExtraneousLanguage',
			new ExtraneousLanguage( $extraneousLanguageFileHandler )
		);
	}

	public function testGetNamespaces() {

		$mockedContent = array(
			SMW_NS_PROPERTY => "Property"
		);

		$language = $this->getMockBuilder( '\SMWLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->atLeastOnce() )
			->method( 'getNamespaces' )
			->will( $this->returnValue( $mockedContent ) );

		$extraneousLanguageFileHandler = $this->getMockBuilder( '\SMW\ExtraneousLanguageFileHandler' )
			->disableOriginalConstructor()
			->getMock();

		$extraneousLanguageFileHandler->expects( $this->atLeastOnce() )
			->method( 'newByLanguageCode' )
			->will( $this->returnValue( $language ) );

		$instance = new ExtraneousLanguage(
			$extraneousLanguageFileHandler
		);

		$this->assertEquals(
			array( SMW_NS_PROPERTY => "Property" ),
			$instance->getNamespaces()
		);
	}

	public function testGetNamespaceAliases() {

		$mockedContent = array(
			"Property" => SMW_NS_PROPERTY
		);

		$language = $this->getMockBuilder( '\SMWLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->atLeastOnce() )
			->method( 'getNamespaceAliases' )
			->will( $this->returnValue( $mockedContent ) );

		$extraneousLanguageFileHandler = $this->getMockBuilder( '\SMW\ExtraneousLanguageFileHandler' )
			->disableOriginalConstructor()
			->getMock();

		$extraneousLanguageFileHandler->expects( $this->atLeastOnce() )
			->method( 'newByLanguageCode' )
			->will( $this->returnValue( $language ) );

		$instance = new ExtraneousLanguage(
			$extraneousLanguageFileHandler
		);

		$this->assertEquals(
			array( "Property" => SMW_NS_PROPERTY ),
			$instance->getNamespaceAliases()
		);
	}

	public function testGetPreferredDateFormatByPrecisionOnMatchedPrecision() {

		$language = $this->getMockBuilder( '\SMWLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->atLeastOnce() )
			->method( 'getPreferredDateFormats' )
			->will( $this->returnValue(
				array( 'SMW_PREC_YMDT' => 'd m Y' ) ) );

		$extraneousLanguageFileHandler = $this->getMockBuilder( '\SMW\ExtraneousLanguageFileHandler' )
			->disableOriginalConstructor()
			->getMock();

		$extraneousLanguageFileHandler->expects( $this->atLeastOnce() )
			->method( 'newByLanguageCode' )
			->will( $this->returnValue( $language ) );

		$instance = new ExtraneousLanguage(
			$extraneousLanguageFileHandler
		);

		$this->assertEquals(
			'd m Y',
			$instance->getPreferredDateFormatByPrecision( SMW_PREC_YMDT )
		);
	}

	public function testGetPreferredDateFormatOnNotMatchablePrecision() {

		$language = $this->getMockBuilder( '\SMWLanguage' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->atLeastOnce() )
			->method( 'getPreferredDateFormats' )
			->will( $this->returnValue( array( 'Foo' => 'd m Y' ) ) );

		$extraneousLanguageFileHandler = $this->getMockBuilder( '\SMW\ExtraneousLanguageFileHandler' )
			->disableOriginalConstructor()
			->getMock();

		$extraneousLanguageFileHandler->expects( $this->atLeastOnce() )
			->method( 'newByLanguageCode' )
			->will( $this->returnValue( $language ) );

		$instance = new ExtraneousLanguage(
			$extraneousLanguageFileHandler
		);

		$this->assertEquals(
			'd F Y H:i:s',
			$instance->getPreferredDateFormatByPrecision( SMW_PREC_YMDT )
		);
	}

}
