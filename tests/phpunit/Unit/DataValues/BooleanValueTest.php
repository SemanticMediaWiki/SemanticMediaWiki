<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\BooleanValue;
use SMW\Localizer;

/**
 * @covers \SMW\DataValues\BooleanValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class BooleanValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\BooleanValue',
			new BooleanValue()
		);
	}

	public function testGetBoolean() {

		$instance = new BooleanValue();

		$this->assertFalse(
			$instance->getBoolean()
		);

		$instance->setUserValue( 'true' );

		$this->assertTrue(
			$instance->getBoolean()
		);
	}

	public function testParseUserValueWhereContentPageHasSpecificContentLanguage() {

		$language = Localizer::getInstance()->getLanguage( 'ja' );

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$instance = new BooleanValue();

		$instance->setContextPage( $subject );
		$instance->setUserValue( '真' );

		$this->assertTrue(
			$instance->getBoolean()
		);

		$this->assertInternalType(
			'string',
			$instance->getWikiValue()
		);
	}

	public function testGetShortWikiTextForLocalizedOutputFormat() {

		$instance = new BooleanValue();

		$instance->setUserValue( 'true' );
		$instance->setOutputFormat( 'LOCL@fr' );

		$this->assertEquals(
			'vrai',
			$instance->getShortWikiText()
		);
	}

	public function testGetShortHTMLTextForLocalizedOutputFormat() {

		$instance = new BooleanValue();

		$instance->setUserValue( 'true' );
		$instance->setOutputFormat( 'LOCL@fr' );

		$this->assertEquals(
			'vrai',
			$instance->getShortHTMLText()
		);
	}

	public function testGetLongWikiTextForLocalizedOutputFormat() {

		$instance = new BooleanValue();

		$instance->setUserValue( 'true' );
		$instance->setOutputFormat( 'LOCL@fr' );

		$this->assertEquals(
			'vrai',
			$instance->getLongWikiText()
		);
	}

	public function testGetLongWikiTextForPlainFormattedValue() {

		$instance = new BooleanValue();

		$instance->setUserValue( 'true' );
		$instance->setOutputFormat( '-' );

		$this->assertEquals(
			'true',
			$instance->getLongWikiText()
		);
	}

}
